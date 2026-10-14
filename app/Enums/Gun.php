<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Teaching days of the weekly grid (Monday to Friday).
 */
enum Gun: int
{
    case Pazartesi = 1;
    case Sali = 2;
    case Carsamba = 3;
    case Persembe = 4;
    case Cuma = 5;

    public function label(): string
    {
        return match ($this) {
            self::Pazartesi => 'Pazartesi',
            self::Sali => 'Salı',
            self::Carsamba => 'Çarşamba',
            self::Persembe => 'Perşembe',
            self::Cuma => 'Cuma',
        };
    }
}
