<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProgramDerecesi;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programlar = [
            ['BP', 'Bilgisayar Programcılığı', 'Bilgisayar Teknolojileri'],
            ['EL', 'Elektrik', 'Elektrik ve Enerji'],
            ['MV', 'Muhasebe ve Vergi Uygulamaları', 'Muhasebe ve Vergi'],
            ['GT', 'Grafik Tasarımı', 'Tasarım'],
        ];

        foreach ($programlar as [$kod, $ad, $bolum]) {
            Program::query()->create([
                'program_kodu' => $kod,
                'program_adi' => $ad,
                'derece' => ProgramDerecesi::OnLisans,
                'bolum_adi' => $bolum,
                'aktif' => true,
            ]);
        }
    }
}
