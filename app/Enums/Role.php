<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Instructor = 'instructor';
    case Teacher = 'teacher';
    case Editor = 'editor';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Süper Yönetici',
            self::Admin => 'Yönetici',
            self::Instructor => 'Öğretim Elemanı',
            self::Teacher => 'Öğretmen',
            self::Editor => 'Editör',
            self::Guest => 'Misafir',
        };
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function canManageSchedule(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::Editor], true);
    }

    /**
     * Whether a user holding this role may grant the given role to someone else.
     * Only super admins may create other super admins or admins.
     */
    public function canGrant(self $role): bool
    {
        if ($this === self::SuperAdmin) {
            return true;
        }

        return $this === self::Admin
            && ! in_array($role, [self::SuperAdmin, self::Admin], true);
    }
}
