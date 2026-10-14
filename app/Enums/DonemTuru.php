<?php

declare(strict_types=1);

namespace App\Enums;

enum DonemTuru: string
{
    case Guz = 'guz';
    case Bahar = 'bahar';
    case Yaz = 'yaz';

    public function label(): string
    {
        return match ($this) {
            self::Guz => 'Güz',
            self::Bahar => 'Bahar',
            self::Yaz => 'Yaz',
        };
    }
}
