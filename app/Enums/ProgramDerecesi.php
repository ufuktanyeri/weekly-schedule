<?php

declare(strict_types=1);

namespace App\Enums;

enum ProgramDerecesi: string
{
    case OnLisans = 'on_lisans';
    case Lisans = 'lisans';

    public function label(): string
    {
        return match ($this) {
            self::OnLisans => 'Ön Lisans',
            self::Lisans => 'Lisans',
        };
    }
}
