<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Onay Bekliyor',
            self::Approved => 'Onaylandı',
            self::Rejected => 'Reddedildi',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === self::Pending && $target !== self::Pending;
    }
}
