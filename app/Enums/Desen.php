<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Visual pattern used together with the HEX colour to tell faculty members apart.
 */
enum Desen: string
{
    case Duz = 'none';
    case Noktali = 'dots';
    case Cizgili = 'stripes';
    case Kareli = 'grid';
    case Capraz = 'diagonal';

    public function label(): string
    {
        return match ($this) {
            self::Duz => 'Düz',
            self::Noktali => 'Noktalı',
            self::Cizgili => 'Çizgili',
            self::Kareli => 'Kareli',
            self::Capraz => 'Çapraz',
        };
    }

    /**
     * CSS background-image used to render the pattern on top of the colour.
     */
    public function css(): string
    {
        return match ($this) {
            self::Duz => 'none',
            self::Noktali => 'radial-gradient(rgba(255,255,255,.45) 1.5px, transparent 1.5px)',
            self::Cizgili => 'repeating-linear-gradient(0deg, rgba(255,255,255,.35) 0 3px, transparent 3px 8px)',
            self::Kareli => 'linear-gradient(rgba(255,255,255,.35) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.35) 1px, transparent 1px)',
            self::Capraz => 'repeating-linear-gradient(45deg, rgba(255,255,255,.35) 0 3px, transparent 3px 8px)',
        };
    }
}
