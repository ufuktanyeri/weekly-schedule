<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthProvider;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'auth_provider' => AuthProvider::Local,
            'role' => Role::Guest,
            'status' => UserStatus::Approved,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn (): array => ['role' => $role]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Rejected]);
    }
}
