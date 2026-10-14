<?php

declare(strict_types=1);

namespace App\Enums;

enum CakismaTuru: string
{
    case OgretimElemani = 'ogretim_elemani';
    case Derslik = 'derslik';
    case MukerrerKayit = 'mukerrer_kayit';
    case IsYuku = 'is_yuku';
    case GecersizSaat = 'gecersiz_saat';
    case OgleArasi = 'ogle_arasi';

    public function label(): string
    {
        return match ($this) {
            self::OgretimElemani => 'Öğretim elemanı çakışması',
            self::Derslik => 'Derslik çakışması',
            self::MukerrerKayit => 'Mükerrer kayıt',
            self::IsYuku => 'Haftalık ders yükü aşımı',
            self::GecersizSaat => 'Geçersiz saat dilimi',
            self::OgleArasi => 'Öğle arası',
        };
    }
}
