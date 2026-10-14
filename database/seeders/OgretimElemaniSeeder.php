<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Desen;
use App\Models\AdminUser;
use App\Models\OgretimElemani;
use Illuminate\Database\Seeder;

class OgretimElemaniSeeder extends Seeder
{
    public function run(): void
    {
        $kayitlar = [
            ['Öğr. Gör.', 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@ankara.edu.tr', '#2563EB', Desen::Duz, 20],
            ['Dr. Öğr. Üyesi', 'Ayşe', 'Demir', 'ayse.demir@ankara.edu.tr', '#DC2626', Desen::Noktali, 20],
            ['Öğr. Gör.', 'Mehmet', 'Kaya', 'mehmet.kaya@ankara.edu.tr', '#16A34A', Desen::Cizgili, 20],
            ['Doç. Dr.', 'Zeynep', 'Çelik', 'zeynep.celik@ankara.edu.tr', '#9333EA', Desen::Kareli, 20],
            ['Öğr. Gör.', 'Mustafa', 'Şahin', 'mustafa.sahin@ankara.edu.tr', '#EA580C', Desen::Capraz, 20],
            ['Öğr. Gör.', 'Elif', 'Arslan', 'elif.arslan@ankara.edu.tr', '#0891B2', Desen::Noktali, 20],
            ['Prof. Dr.', 'Hasan', 'Öztürk', 'hasan.ozturk@ankara.edu.tr', '#DB2777', Desen::Cizgili, 20],
            // Part-time lecturer with a reduced weekly limit.
            ['Öğr. Gör.', 'Fatma', 'Koç', 'fatma.koc@ankara.edu.tr', '#65A30D', Desen::Duz, 12],
        ];

        foreach ($kayitlar as [$unvan, $ad, $soyad, $eposta, $renk, $desen, $limit]) {
            OgretimElemani::query()->create([
                'admin_user_id' => AdminUser::query()->where('email', $eposta)->value('id'),
                'unvan' => $unvan,
                'ad' => $ad,
                'soyad' => $soyad,
                'eposta' => $eposta,
                'renk_kodu' => $renk,
                'desen' => $desen,
                'haftalik_ders_limiti' => $limit,
                'aktif' => true,
            ]);
        }
    }
}
