<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Ders;
use App\Models\Program;
use Illuminate\Database\Seeder;

class DersSeeder extends Seeder
{
    public function run(): void
    {
        // [program, kod, ad, sınıf, teorik, uygulama, kredi, akts]
        $dersler = [
            ['BP', 'BP101', 'Algoritma ve Programlama', 1, 2, 2, 3, 4],
            ['BP', 'BP103', 'Web Tasarımın Temelleri', 1, 2, 2, 3, 4],
            ['BP', 'BP105', 'Veritabanı Yönetim Sistemleri', 1, 2, 2, 3, 4],
            ['BP', 'BP201', 'Nesne Tabanlı Programlama', 2, 2, 2, 3, 5],
            ['EL', 'EL101', 'Doğru Akım Devre Analizi', 1, 2, 2, 3, 4],
            ['EL', 'EL103', 'Elektrik Atölyesi', 1, 1, 3, 3, 5],
            ['EL', 'EL105', 'Teknik Resim', 1, 1, 1, 2, 2],
            ['EL', 'EL201', 'Elektrik Makineleri', 2, 2, 2, 3, 5],
            ['MV', 'MV101', 'Genel Muhasebe', 1, 3, 0, 3, 5],
            ['MV', 'MV103', 'Ticaret Hukuku', 1, 2, 0, 2, 3],
            ['MV', 'MV105', 'Mikro Ekonomi', 1, 2, 0, 2, 3],
            ['MV', 'MV201', 'Vergi Mevzuatı', 2, 3, 0, 3, 4],
            ['GT', 'GT101', 'Temel Tasarım', 1, 2, 2, 3, 5],
            ['GT', 'GT103', 'Tipografi', 1, 2, 1, 3, 4],
            ['GT', 'GT105', 'Bilgisayarlı Grafik', 1, 1, 3, 3, 5],
            ['GT', 'GT201', 'Ambalaj Tasarımı', 2, 2, 2, 3, 5],
        ];

        $programlar = Program::query()->pluck('id', 'program_kodu');

        foreach ($dersler as [$program, $kod, $ad, $sinif, $teorik, $uygulama, $kredi, $akts]) {
            Ders::query()->create([
                'program_id' => $programlar[$program],
                'ders_kodu' => $kod,
                'ders_adi' => $ad,
                'sinif' => $sinif,
                'teorik_saat' => $teorik,
                'uygulama_saat' => $uygulama,
                'kredi' => $kredi,
                'akts' => $akts,
                'aktif' => true,
            ]);
        }
    }
}
