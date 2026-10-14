<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AkademikDonem;
use App\Models\DersAtama;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use Illuminate\Contracts\Config\Repository;

/**
 * Weekly teaching load calculations for faculty members.
 */
final class WorkloadService
{
    public function __construct(
        private readonly Repository $config,
        private readonly TimeSlotService $timeSlots,
    ) {}

    public function defaultLimitHours(): int
    {
        return (int) $this->config->get('schedule.max_weekly_hours', 20);
    }

    public function limitHours(OgretimElemani $ogretimElemani): int
    {
        return $ogretimElemani->haftalik_ders_limiti > 0
            ? $ogretimElemani->haftalik_ders_limiti
            : $this->defaultLimitHours();
    }

    /**
     * Hours allocated through ders_atamalari for the given term.
     */
    public function assignedHours(OgretimElemani $ogretimElemani, AkademikDonem $donem): int
    {
        return (int) DersAtama::query()
            ->where('ogretim_elemani_id', $ogretimElemani->id)
            ->where('akademik_donem_id', $donem->id)
            ->sum('haftalik_saat');
    }

    /**
     * Minutes already placed on the weekly grid for the given term.
     */
    public function scheduledMinutes(OgretimElemani $ogretimElemani, AkademikDonem $donem): int
    {
        return HaftalikProgram::query()
            ->where('ogretim_elemani_id', $ogretimElemani->id)
            ->where('akademik_donem_id', $donem->id)
            ->count() * $this->timeSlots->slotMinutes();
    }

    /**
     * @return list<array{ogretim_elemani: OgretimElemani, atanan_saat: int, programlanan_saat: float, limit: int}>
     */
    public function summary(AkademikDonem $donem): array
    {
        return array_values(OgretimElemani::query()
            ->where('aktif', true)
            ->orderBy('ad')
            ->orderBy('soyad')
            ->get()
            ->map(fn (OgretimElemani $oe): array => [
                'ogretim_elemani' => $oe,
                'atanan_saat' => $this->assignedHours($oe, $donem),
                'programlanan_saat' => round($this->scheduledMinutes($oe, $donem) / 60, 1),
                'limit' => $this->limitHours($oe),
            ])
            ->all());
    }
}
