<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CakismaTuru;
use App\Enums\Gun;
use App\Models\AdminUser;
use App\Models\CakismaLogu;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use App\Support\Conflict;
use App\Support\TimeSlot;
use InvalidArgumentException;

/**
 * Validates scheduling and allocation requests against the business rules:
 *  - slots must be on the 30-minute grid and outside the lunch break,
 *  - an instructor cannot be in two places in the same slot,
 *  - a classroom cannot host two different courses in the same slot,
 *  - an instructor's weekly load cannot exceed their limit.
 *
 * Rejected attempts are persisted to "cakisma_loglari" via log().
 */
final class ConflictValidationService
{
    public function __construct(
        private readonly TimeSlotService $timeSlots,
        private readonly WorkloadService $workload,
    ) {}

    /**
     * @return list<Conflict>
     */
    public function validateScheduleEntry(DersAtama $atama, Derslik $derslik, Gun $gun, string $baslangic, string $bitis): array
    {
        try {
            $slots = $this->timeSlots->between($baslangic, $bitis);
        } catch (InvalidArgumentException $e) {
            return [new Conflict(CakismaTuru::GecersizSaat, $e->getMessage())];
        }

        $conflicts = [];

        foreach ($slots as $slot) {
            if ($this->timeSlots->isLunch($slot)) {
                $conflicts[] = new Conflict(
                    CakismaTuru::OgleArasi,
                    "{$gun->label()} {$slot->label()} öğle arasına denk geliyor.",
                    $slot,
                );
            }
        }

        if ($conflicts !== []) {
            return $conflicts;
        }

        $starts = array_map(static fn (TimeSlot $s): string => $s->start, $slots);
        $bySlot = [];
        foreach ($slots as $slot) {
            $bySlot[$slot->start] = $slot;
        }

        $existing = HaftalikProgram::query()
            ->with(['dersAtama.ders', 'ogretimElemani', 'derslik'])
            ->where('akademik_donem_id', $atama->akademik_donem_id)
            ->where('gun', $gun->value)
            ->whereIn('baslangic_saati', $starts)
            ->where(function ($query) use ($atama, $derslik): void {
                $query->where('ogretim_elemani_id', $atama->ogretim_elemani_id)
                    ->orWhere('derslik_id', $derslik->id)
                    ->orWhere('ders_atama_id', $atama->id);
            })
            ->orderBy('baslangic_saati')
            ->get();

        foreach ($existing as $kayit) {
            $slot = $bySlot[$kayit->baslangic_saati];
            $mevcutDers = $kayit->dersAtama->ders;

            if ($kayit->ders_atama_id === $atama->id) {
                $conflicts[] = new Conflict(
                    CakismaTuru::MukerrerKayit,
                    "Bu ders ataması {$gun->label()} {$slot->label()} diliminde zaten programda.",
                    $slot,
                    $kayit->id,
                );

                continue;
            }

            if ($kayit->ogretim_elemani_id === $atama->ogretim_elemani_id) {
                $conflicts[] = new Conflict(
                    CakismaTuru::OgretimElemani,
                    sprintf(
                        '%s, %s %s diliminde %s dersinde görevli.',
                        $kayit->ogretimElemani->tamAd(),
                        $gun->label(),
                        $slot->label(),
                        $this->dersEtiketi($mevcutDers),
                    ),
                    $slot,
                    $kayit->id,
                );
            }

            if ($kayit->derslik_id === $derslik->id && $kayit->dersAtama->ders_id !== $atama->ders_id) {
                $conflicts[] = new Conflict(
                    CakismaTuru::Derslik,
                    sprintf(
                        '%s dersliği %s %s diliminde %s dersi için dolu.',
                        $kayit->derslik->derslik_kodu,
                        $gun->label(),
                        $slot->label(),
                        $this->dersEtiketi($mevcutDers),
                    ),
                    $slot,
                    $kayit->id,
                );
            }
        }

        $ogretimElemani = $atama->ogretimElemani;
        $donem = $atama->akademikDonem;
        $limitMinutes = $this->workload->limitHours($ogretimElemani) * 60;
        $newMinutes = count($slots) * $this->timeSlots->slotMinutes();
        $currentMinutes = $this->workload->scheduledMinutes($ogretimElemani, $donem);

        if ($currentMinutes + $newMinutes > $limitMinutes) {
            $conflicts[] = new Conflict(
                CakismaTuru::IsYuku,
                sprintf(
                    '%s için haftalık ders yükü sınırı (%d saat) aşılıyor: mevcut %s saat + %s saat.',
                    $ogretimElemani->tamAd(),
                    $limitMinutes / 60,
                    $this->saat($currentMinutes),
                    $this->saat($newMinutes),
                ),
            );
        }

        return $conflicts;
    }

    /**
     * Validates allocating a course to an instructor for a term.
     *
     * @return list<Conflict>
     */
    public function validateAssignment(int $akademikDonemId, Ders $ders, OgretimElemani $ogretimElemani, int $haftalikSaat, string $sube): array
    {
        $conflicts = [];

        $exists = DersAtama::query()
            ->where('akademik_donem_id', $akademikDonemId)
            ->where('ders_id', $ders->id)
            ->where('ogretim_elemani_id', $ogretimElemani->id)
            ->where('sube', $sube)
            ->exists();

        if ($exists) {
            $conflicts[] = new Conflict(
                CakismaTuru::MukerrerKayit,
                sprintf('%s dersi (%s şubesi) bu dönem için %s adına zaten atanmış.', $this->dersEtiketi($ders), $sube, $ogretimElemani->tamAd()),
            );
        }

        $assigned = (int) DersAtama::query()
            ->where('akademik_donem_id', $akademikDonemId)
            ->where('ogretim_elemani_id', $ogretimElemani->id)
            ->sum('haftalik_saat');
        $limit = $this->workload->limitHours($ogretimElemani);

        if ($assigned + $haftalikSaat > $limit) {
            $conflicts[] = new Conflict(
                CakismaTuru::IsYuku,
                sprintf(
                    '%s için haftalık ders yükü sınırı (%d saat) aşılıyor: mevcut %d saat + %d saat.',
                    $ogretimElemani->tamAd(),
                    $limit,
                    $assigned,
                    $haftalikSaat,
                ),
            );
        }

        return $conflicts;
    }

    /**
     * Persist rejected attempts to the conflict log.
     *
     * @param  list<Conflict>  $conflicts
     * @param  array{akademik_donem_id?: int|null, ders_atama_id?: int|null, ders_id?: int|null, ogretim_elemani_id?: int|null, derslik_id?: int|null, gun?: Gun|null}  $context
     */
    public function log(array $conflicts, array $context, ?AdminUser $actor = null): void
    {
        foreach ($conflicts as $conflict) {
            CakismaLogu::query()->create([
                'cakisma_turu' => $conflict->tur,
                'akademik_donem_id' => $context['akademik_donem_id'] ?? null,
                'ders_atama_id' => $context['ders_atama_id'] ?? null,
                'ders_id' => $context['ders_id'] ?? null,
                'ogretim_elemani_id' => $context['ogretim_elemani_id'] ?? null,
                'derslik_id' => $context['derslik_id'] ?? null,
                'gun' => $context['gun'] ?? null,
                'cakisan_kayit_id' => $conflict->cakisanKayitId,
                'admin_user_id' => $actor?->id,
                'baslangic_saati' => $conflict->slot?->start,
                'bitis_saati' => $conflict->slot?->end,
                'mesaj' => $conflict->mesaj,
            ]);
        }
    }

    private function dersEtiketi(Ders $ders): string
    {
        return $ders->ders_kodu.' '.$ders->ders_adi;
    }

    private function saat(int $minutes): string
    {
        return rtrim(rtrim(number_format($minutes / 60, 1, ',', ''), '0'), ',');
    }
}
