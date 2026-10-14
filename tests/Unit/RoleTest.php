<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use App\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_role_values_match_the_specification(): void
    {
        $this->assertSame(
            ['super_admin', 'admin', 'instructor', 'teacher', 'editor', 'guest'],
            array_map(fn (Role $r): string => $r->value, Role::cases()),
        );
    }

    public function test_permissions(): void
    {
        $this->assertTrue(Role::SuperAdmin->canManageUsers());
        $this->assertTrue(Role::Admin->canManageUsers());
        $this->assertFalse(Role::Editor->canManageUsers());
        $this->assertFalse(Role::Guest->canManageUsers());

        $this->assertTrue(Role::Editor->canManageSchedule());
        $this->assertFalse(Role::Instructor->canManageSchedule());
        $this->assertFalse(Role::Teacher->canManageSchedule());
    }

    public function test_only_super_admin_can_grant_privileged_roles(): void
    {
        $this->assertTrue(Role::SuperAdmin->canGrant(Role::SuperAdmin));
        $this->assertTrue(Role::SuperAdmin->canGrant(Role::Admin));
        $this->assertFalse(Role::Admin->canGrant(Role::SuperAdmin));
        $this->assertFalse(Role::Admin->canGrant(Role::Admin));
        $this->assertTrue(Role::Admin->canGrant(Role::Instructor));
        $this->assertFalse(Role::Editor->canGrant(Role::Guest));
    }

    public function test_status_transitions_only_leave_pending(): void
    {
        $this->assertTrue(UserStatus::Pending->canTransitionTo(UserStatus::Approved));
        $this->assertTrue(UserStatus::Pending->canTransitionTo(UserStatus::Rejected));
        $this->assertFalse(UserStatus::Pending->canTransitionTo(UserStatus::Pending));
        $this->assertFalse(UserStatus::Approved->canTransitionTo(UserStatus::Rejected));
        $this->assertFalse(UserStatus::Rejected->canTransitionTo(UserStatus::Approved));
    }
}
