<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DerslikTuru;
use App\Models\Derslik;
use Illuminate\Database\Seeder;

class DerslikSeeder extends Seeder
{
    public function run(): void
    {
        $derslikler = [
            ['D101', 'Derslik 101', 40, DerslikTuru::Sinif, 'A Blok'],
            ['D102', 'Derslik 102', 40, DerslikTuru::Sinif, 'A Blok'],
            ['AMF1', 'Konferans Amfisi', 120, DerslikTuru::Amfi, 'Merkez Bina'],
            ['BLAB1', 'Bilgisayar Laboratuvarı 1', 30, DerslikTuru::BilgisayarLab, 'B Blok'],
            ['ELAB1', 'Elektrik Laboratuvarı', 24, DerslikTuru::Laboratuvar, 'C Blok'],
            ['ATL1', 'Uygulama Atölyesi', 20, DerslikTuru::Atolye, 'C Blok'],
        ];

        foreach ($derslikler as [$kod, $ad, $kapasite, $tur, $bina]) {
            Derslik::query()->create([
                'derslik_kodu' => $kod,
                'derslik_adi' => $ad,
                'kapasite' => $kapasite,
                'tur' => $tur,
                'bina' => $bina,
                'aktif' => true,
            ]);
        }
    }
}
