<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CakismaTuru;
use App\Enums\Gun;
use App\Exceptions\ScheduleConflictException;
use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use App\Support\Conflict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Application service for course allocations and the weekly schedule grid.
 */
final class ProgramService
{
    public function __construct(
        private readonly ConflictValidationService $validator,
        private readonly TimeSlotService $timeSlots,
    ) {}

    /**
     * Allocate a course to a faculty member for the given term, enforcing the
     * weekly workload limit.
     *
     * @throws ScheduleConflictException
     */
    public function assignCourse(
        AkademikDonem $donem,
        Ders $ders,
        OgretimElemani $ogretimElemani,
        ?int $haftalikSaat = null,
        string $sube = 'A',
        ?AdminUser $actor = null,
    ): DersAtama {
        $haftalikSaat ??= $ders->haftalikSaat();

        /** @var DersAtama|list<Conflict> $result */
        $result = DB::transaction(function () use ($donem, $ders, $ogretimElemani, $haftalikSaat, $sube): DersAtama|array {
            // Serialise concurrent allocations for the same instructor.
            OgretimElemani::query()->whereKey($ogretimElemani->id)->lockForUpdate()->first();

            $conflicts = $this->validator->validateAssignment($donem->id, $ders, $ogretimElemani, $haftalikSaat, $sube);
            if ($conflicts !== []) {
                return $conflicts;
            }

            return DersAtama::query()->create([
                'akademik_donem_id' => $donem->id,
                'ders_id' => $ders->id,
                'ogretim_elemani_id' => $ogretimElemani->id,
                'haftalik_saat' => $haftalikSaat,
                'sube' => $sube,
            ]);
        });

        if ($result instanceof DersAtama) {
            return $result;
        }

        $this->fail($result, [
            'akademik_donem_id' => $donem->id,
            'ders_id' => $ders->id,
            'ogretim_elemani_id' => $ogretimElemani->id,
        ], $actor);
    }

    /**
     * Place an allocation on the weekly grid for the [baslangic, bitis) range.
     * Either every 30-minute slot is created or none is.
     *
     * @return Collection<int, HaftalikProgram>
     *
     * @throws ScheduleConflictException
     */
    public function schedule(
        DersAtama $atama,
        Derslik $derslik,
        Gun $gun,
        string $baslangic,
        string $bitis,
        ?AdminUser $actor = null,
    ): Collection {
        $context = [
            'akademik_donem_id' => $atama->akademik_donem_id,
            'ders_atama_id' => $atama->id,
            'ders_id' => $atama->ders_id,
            'ogretim_elemani_id' => $atama->ogretim_elemani_id,
            'derslik_id' => $derslik->id,
            'gun' => $gun,
        ];

        try {
            /** @var Collection<int, HaftalikProgram>|list<Conflict> $result */
            $result = DB::transaction(function () use ($atama, $derslik, $gun, $baslangic, $bitis): Collection|array {
                // Serialise concurrent writes touching the same classroom or instructor.
                Derslik::query()->whereKey($derslik->id)->lockForUpdate()->first();
                OgretimElemani::query()->whereKey($atama->ogretim_elemani_id)->lockForUpdate()->first();

                $conflicts = $this->validator->validateScheduleEntry($atama, $derslik, $gun, $baslangic, $bitis);
                if ($conflicts !== []) {
                    return $conflicts;
                }

                $created = new Collection;
                foreach ($this->timeSlots->between($baslangic, $bitis) as $slot) {
                    $created->push(HaftalikProgram::query()->create([
                        'ders_atama_id' => $atama->id,
                        'akademik_donem_id' => $atama->akademik_donem_id,
                        'ogretim_elemani_id' => $atama->ogretim_elemani_id,
                        'derslik_id' => $derslik->id,
                        'gun' => $gun,
                        'baslangic_saati' => $slot->start,
                        'bitis_saati' => $slot->end,
                    ]));
                }

                return $created;
            });
        } catch (UniqueConstraintViolationException) {
            // Lost a race against a concurrent request on the unique slot indexes.
            $this->fail([new Conflict(
                CakismaTuru::OgretimElemani,
                'Seçilen dilim eş zamanlı başka bir işlemle dolduruldu, lütfen tekrar deneyin.',
            )], $context, $actor);
        }

        if ($result instanceof Collection) {
            return $result;
        }

        $this->fail($result, $context, $actor);
    }

    public function removeEntry(HaftalikProgram $kayit): void
    {
        $kayit->delete();
    }

    /**
     * Grid data indexed by day value and slot start time.
     *
     * @return array<int, array<string, list<HaftalikProgram>>>
     */
    public function grid(AkademikDonem $donem, ?int $programId = null, ?int $ogretimElemaniId = null, ?int $derslikId = null): array
    {
        $kayitlar = HaftalikProgram::query()
            ->with(['dersAtama.ders.program', 'ogretimElemani', 'derslik'])
            ->where('akademik_donem_id', $donem->id)
            ->when($programId !== null, fn (Builder $q) => $q->whereHas(
                'dersAtama.ders',
                fn (Builder $d) => $d->where('program_id', $programId),
            ))
            ->when($ogretimElemaniId !== null, fn (Builder $q) => $q->where('ogretim_elemani_id', $ogretimElemaniId))
            ->when($derslikId !== null, fn (Builder $q) => $q->where('derslik_id', $derslikId))
            ->orderBy('gun')
            ->orderBy('baslangic_saati')
            ->get();

        $grid = [];
        foreach (Gun::cases() as $gun) {
            foreach ($this->timeSlots->allSlots() as $slot) {
                $grid[$gun->value][$slot->start] = [];
            }
        }

        foreach ($kayitlar as $kayit) {
            $grid[$kayit->gun->value][$kayit->baslangic_saati][] = $kayit;
        }

        return $grid;
    }

    /**
     * @param  list<Conflict>  $conflicts
     * @param  array{akademik_donem_id?: int|null, ders_atama_id?: int|null, ders_id?: int|null, ogretim_elemani_id?: int|null, derslik_id?: int|null, gun?: Gun|null}  $context
     *
     * @throws ScheduleConflictException
     */
    private function fail(array $conflicts, array $context, ?AdminUser $actor): never
    {
        $this->validator->log($conflicts, $context, $actor);

        if ($conflicts === []) {
            $conflicts = [new Conflict(CakismaTuru::GecersizSaat, 'Bilinmeyen çakışma.')];
        }

        throw new ScheduleConflictException($conflicts);
    }
}
