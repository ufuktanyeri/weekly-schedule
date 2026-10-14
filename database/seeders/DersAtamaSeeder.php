<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Exceptions\ScheduleConflictException;
use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\Ders;
use App\Models\OgretimElemani;
use App\Services\ProgramService;
use Illuminate\Database\Seeder;

class DersAtamaSeeder extends Seeder
{
    /**
     * [ders kodu, şube, öğretim elemanı e-postası]
     */
    public const ATAMALAR = [
        ['BP101', 'A', 'ahmet.yilmaz@ankara.edu.tr'],
        ['BP101', 'B', 'ahmet.yilmaz@ankara.edu.tr'],
        ['BP103', 'A', 'ayse.demir@ankara.edu.tr'],
        ['BP105', 'A', 'ahmet.yilmaz@ankara.edu.tr'],
        ['BP201', 'A', 'ayse.demir@ankara.edu.tr'],
        ['EL101', 'A', 'mehmet.kaya@ankara.edu.tr'],
        ['EL103', 'A', 'mustafa.sahin@ankara.edu.tr'],
        ['EL105', 'A', 'mehmet.kaya@ankara.edu.tr'],
        ['EL201', 'A', 'mustafa.sahin@ankara.edu.tr'],
        ['MV101', 'A', 'zeynep.celik@ankara.edu.tr'],
        ['MV103', 'A', 'hasan.ozturk@ankara.edu.tr'],
        ['MV105', 'A', 'zeynep.celik@ankara.edu.tr'],
        ['MV201', 'A', 'hasan.ozturk@ankara.edu.tr'],
        ['GT101', 'A', 'elif.arslan@ankara.edu.tr'],
        ['GT103', 'A', 'fatma.koc@ankara.edu.tr'],
        ['GT105', 'A', 'elif.arslan@ankara.edu.tr'],
        ['GT201', 'A', 'fatma.koc@ankara.edu.tr'],
    ];

    public function run(ProgramService $programs): void
    {
        $donem = AkademikDonem::query()->aktif()->firstOrFail();
        $actor = AdminUser::query()->where('email', 'super@ankara.edu.tr')->first();

        foreach (self::ATAMALAR as [$kod, $sube, $eposta]) {
            $programs->assignCourse(
                $donem,
                Ders::query()->where('ders_kodu', $kod)->firstOrFail(),
                OgretimElemani::query()->where('eposta', $eposta)->firstOrFail(),
                null,
                $sube,
                $actor,
            );
        }

        // Sample rejected allocation: exceeds the 12h limit of the part-time lecturer.
        try {
            $programs->assignCourse(
                $donem,
                Ders::query()->where('ders_kodu', 'BP103')->firstOrFail(),
                OgretimElemani::query()->where('eposta', 'fatma.koc@ankara.edu.tr')->firstOrFail(),
                6,
                'B',
                $actor,
            );
        } catch (ScheduleConflictException) {
            // Expected: recorded in cakisma_loglari.
        }
    }
}
