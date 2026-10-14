<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Gun;
use App\Exceptions\ScheduleConflictException;
use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Services\ProgramService;
use Illuminate\Database\Seeder;

class HaftalikProgramSeeder extends Seeder
{
    /**
     * Conflict-free weekly plan: [ders kodu, şube, derslik, gün, başlangıç, bitiş].
     */
    public const PLAN = [
        ['BP101', 'A', 'BLAB1', Gun::Pazartesi, '08:30', '12:30'],
        ['BP103', 'A', 'BLAB1', Gun::Pazartesi, '14:00', '18:00'],
        ['BP105', 'A', 'BLAB1', Gun::Sali, '08:30', '12:30'],
        ['BP201', 'A', 'BLAB1', Gun::Sali, '14:00', '18:00'],
        ['BP101', 'B', 'BLAB1', Gun::Carsamba, '08:30', '12:30'],
        ['EL101', 'A', 'ELAB1', Gun::Pazartesi, '08:30', '12:30'],
        ['EL103', 'A', 'ATL1', Gun::Pazartesi, '14:00', '18:00'],
        ['EL105', 'A', 'D101', Gun::Sali, '09:00', '11:00'],
        ['EL201', 'A', 'ELAB1', Gun::Carsamba, '14:00', '18:00'],
        ['MV101', 'A', 'AMF1', Gun::Pazartesi, '09:00', '12:00'],
        ['MV103', 'A', 'D102', Gun::Sali, '14:00', '16:00'],
        ['MV105', 'A', 'D102', Gun::Carsamba, '09:00', '11:00'],
        ['MV201', 'A', 'AMF1', Gun::Persembe, '14:00', '17:00'],
        ['GT101', 'A', 'ATL1', Gun::Sali, '08:30', '12:30'],
        ['GT103', 'A', 'D101', Gun::Persembe, '09:00', '12:00'],
        ['GT105', 'A', 'BLAB1', Gun::Persembe, '08:30', '12:30'],
        ['GT201', 'A', 'ATL1', Gun::Cuma, '14:00', '18:00'],
    ];

    /**
     * Intentionally invalid attempts that populate the conflict log.
     */
    public const HATALI_DENEMELER = [
        ['MV103', 'A', 'BLAB1', Gun::Pazartesi, '09:00', '10:00'], // classroom busy (BP101)
        ['EL105', 'A', 'D101', Gun::Pazartesi, '10:00', '11:00'],  // instructor busy (EL101)
        ['GT103', 'A', 'D101', Gun::Cuma, '12:00', '13:00'],       // lunch break
    ];

    public function run(ProgramService $programs): void
    {
        $donem = AkademikDonem::query()->aktif()->firstOrFail();
        $actor = AdminUser::query()->where('email', 'super@ankara.edu.tr')->first();

        foreach (self::PLAN as [$kod, $sube, $derslik, $gun, $baslangic, $bitis]) {
            $programs->schedule($this->atama($donem, $kod, $sube), $this->derslik($derslik), $gun, $baslangic, $bitis, $actor);
        }

        foreach (self::HATALI_DENEMELER as [$kod, $sube, $derslik, $gun, $baslangic, $bitis]) {
            try {
                $programs->schedule($this->atama($donem, $kod, $sube), $this->derslik($derslik), $gun, $baslangic, $bitis, $actor);
            } catch (ScheduleConflictException) {
                // Expected: recorded in cakisma_loglari.
            }
        }
    }

    private function atama(AkademikDonem $donem, string $dersKodu, string $sube): DersAtama
    {
        return DersAtama::query()
            ->where('akademik_donem_id', $donem->id)
            ->where('sube', $sube)
            ->whereHas('ders', fn ($q) => $q->where('ders_kodu', $dersKodu))
            ->firstOrFail();
    }

    private function derslik(string $kod): Derslik
    {
        return Derslik::query()->where('derslik_kodu', $kod)->firstOrFail();
    }
}
