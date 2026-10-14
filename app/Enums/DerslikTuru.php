<?php

declare(strict_types=1);

namespace App\Enums;

enum DerslikTuru: string
{
    case Sinif = 'sinif';
    case Amfi = 'amfi';
    case Laboratuvar = 'laboratuvar';
    case BilgisayarLab = 'bilgisayar_lab';
    case Atolye = 'atolye';

    public function label(): string
    {
        return match ($this) {
            self::Sinif => 'Sınıf',
            self::Amfi => 'Amfi',
            self::Laboratuvar => 'Laboratuvar',
            self::BilgisayarLab => 'Bilgisayar Laboratuvarı',
            self::Atolye => 'Atölye',
        };
    }
}
