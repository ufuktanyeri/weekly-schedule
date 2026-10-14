<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthProvider;
use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\AdminUserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property AuthProvider $auth_provider
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property Role $role
 * @property UserStatus $status
 * @property Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, UserApprovalRequest> $approvalRequests
 * @property-read OgretimElemani|null $ogretimElemani
 */
class AdminUser extends Authenticatable
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    /**
     * Role and status are intentionally not mass assignable; they may only be
     * changed through AuthService.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'auth_provider',
        'google_id',
        'avatar_url',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'auth_provider' => AuthProvider::class,
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * @return HasMany<UserApprovalRequest, $this>
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(UserApprovalRequest::class, 'admin_user_id');
    }

    /**
     * @return HasOne<OgretimElemani, $this>
     */
    public function ogretimElemani(): HasOne
    {
        return $this->hasOne(OgretimElemani::class, 'admin_user_id');
    }

    public function isApproved(): bool
    {
        return $this->status === UserStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === UserStatus::Pending;
    }
}
