<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DonemTuru;
use App\Models\AkademikDonem;
use Illuminate\Database\Seeder;

class AkademikDonemSeeder extends Seeder
{
    public function run(): void
    {
        AkademikDonem::query()->create([
            'egitim_yili' => '2025-2026',
            'donem' => DonemTuru::Guz,
            'baslangic_tarihi' => '2025-09-22',
            'bitis_tarihi' => '2026-01-16',
            'aktif' => true,
        ]);

        AkademikDonem::query()->create([
            'egitim_yili' => '2025-2026',
            'donem' => DonemTuru::Bahar,
            'baslangic_tarihi' => '2026-02-16',
            'bitis_tarihi' => '2026-06-12',
            'aktif' => false,
        ]);
    }
}
