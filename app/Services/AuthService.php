<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthProvider;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\AuthenticationFailedException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\AdminUser;
use App\Models\UserApprovalRequest;
use App\Support\GoogleUserData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Registration, login and the pending → approved / rejected workflow.
 */
final class AuthService
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly Repository $config,
    ) {}

    /**
     * Create a local account in the "pending" state with an approval request.
     */
    public function register(string $name, string $email, string $password, Role $requestedRole = Role::Guest, ?string $note = null): AdminUser
    {
        return DB::transaction(function () use ($name, $email, $password, $requestedRole, $note): AdminUser {
            $user = new AdminUser([
                'name' => $name,
                'email' => Str::lower($email),
                'password' => $password,
                'auth_provider' => AuthProvider::Local,
            ]);
            // Local e-mail addresses are unverified, so domain-based super admin
            // promotion is deliberately NOT applied here.
            $user->forceFill(['role' => Role::Guest, 'status' => UserStatus::Pending])->save();

            $this->openApprovalRequest($user, $requestedRole, $note);

            return $user;
        });
    }

    /**
     * Authenticate with local credentials. Only approved accounts may log in.
     *
     * @throws AuthenticationFailedException
     */
    public function login(string $email, string $password, bool $remember = false): AdminUser
    {
        $user = AdminUser::query()->where('email', Str::lower($email))->first();

        if ($user === null || $user->password === null || ! Hash::check($password, $user->password)) {
            throw new AuthenticationFailedException('E-posta adresi veya şifre hatalı.');
        }

        $this->ensureCanLogin($user);

        $this->guard->login($user, $remember);
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    /**
     * Find, link or create the account for a Google identity. The user is
     * logged in only when the account is approved.
     *
     * @throws AuthenticationFailedException
     */
    public function loginWithGoogle(GoogleUserData $google): AdminUser
    {
        if (! $google->emailVerified) {
            throw new AuthenticationFailedException('Google hesabınızın e-posta adresi doğrulanmamış.');
        }

        $email = Str::lower($google->email);
        $isSuperAdminDomain = $this->isSuperAdminDomain($email);

        $user = DB::transaction(function () use ($google, $email, $isSuperAdminDomain): AdminUser {
            $user = AdminUser::query()->where('google_id', $google->id)->first()
                ?? AdminUser::query()->where('email', $email)->first();

            if ($user !== null && $user->google_id !== null && $user->google_id !== $google->id) {
                throw new AuthenticationFailedException('Bu e-posta adresi başka bir Google hesabına bağlı.');
            }

            if ($user === null) {
                $user = new AdminUser([
                    'name' => $google->name,
                    'email' => $email,
                    'auth_provider' => AuthProvider::Google,
                ]);
                $user->forceFill([
                    'role' => $isSuperAdminDomain ? Role::SuperAdmin : Role::Guest,
                    'status' => $isSuperAdminDomain ? UserStatus::Approved : UserStatus::Pending,
                ]);
            } elseif ($isSuperAdminDomain && $user->isPending()) {
                $user->forceFill(['role' => Role::SuperAdmin, 'status' => UserStatus::Approved]);
            }

            $user->forceFill([
                'google_id' => $google->id,
                'avatar_url' => $google->avatarUrl,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            if ($user->isPending() && ! $user->approvalRequests()->where('status', UserStatus::Pending)->exists()) {
                $this->openApprovalRequest($user, Role::Guest, 'Google ile kayıt');
            }

            if ($user->isApproved()) {
                $user->approvalRequests()->where('status', UserStatus::Pending)->update([
                    'status' => UserStatus::Approved,
                    'reviewed_at' => now(),
                    'review_note' => 'Kurumsal alan adı ile otomatik onay',
                ]);
            }

            return $user;
        });

        if ($user->status === UserStatus::Rejected) {
            throw new AuthenticationFailedException('Hesabınız reddedilmiştir.');
        }

        if ($user->isApproved()) {
            $this->guard->login($user, true);
            $user->forceFill(['last_login_at' => now()])->save();
        }

        return $user;
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidStatusTransitionException
     */
    public function approve(AdminUser $user, AdminUser $reviewer, ?Role $role = null, ?string $note = null): AdminUser
    {
        $role ??= $this->findPendingRequest($user)->requested_role ?? Role::Guest;

        if (! $reviewer->role->canManageUsers() || ! $reviewer->role->canGrant($role)) {
            throw new AuthorizationException("Bu kullanıcıya [{$role->label()}] rolünü verme yetkiniz yok.");
        }

        return $this->transition($user, $reviewer, UserStatus::Approved, $role, $note);
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidStatusTransitionException
     */
    public function reject(AdminUser $user, AdminUser $reviewer, ?string $reason = null): AdminUser
    {
        if (! $reviewer->role->canManageUsers()) {
            throw new AuthorizationException('Kullanıcı başvurularını değerlendirme yetkiniz yok.');
        }

        return $this->transition($user, $reviewer, UserStatus::Rejected, null, $reason);
    }

    /**
     * Exact (case-insensitive) match against the configured domains; subdomains
     * such as "ogr.ankara.edu.tr" do not qualify.
     */
    public function isSuperAdminDomain(string $email): bool
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        $domain = Str::lower(substr($email, $at + 1));

        /** @var list<string> $domains */
        $domains = (array) $this->config->get('schedule.super_admin_domains', []);

        foreach ($domains as $allowed) {
            if ($domain === Str::lower(ltrim(trim($allowed), '@'))) {
                return true;
            }
        }

        return false;
    }

    public function logout(): void
    {
        $this->guard->logout();
    }

    /**
     * @throws AuthenticationFailedException
     */
    private function ensureCanLogin(AdminUser $user): void
    {
        match ($user->status) {
            UserStatus::Approved => null,
            UserStatus::Pending => throw new AuthenticationFailedException('Hesabınız yönetici onayı bekliyor.'),
            UserStatus::Rejected => throw new AuthenticationFailedException('Hesabınız reddedilmiştir.'),
        };
    }

    private function transition(AdminUser $user, AdminUser $reviewer, UserStatus $target, ?Role $role, ?string $note): AdminUser
    {
        return DB::transaction(function () use ($user, $reviewer, $target, $role, $note): AdminUser {
            /** @var AdminUser $locked */
            $locked = AdminUser::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($target)) {
                throw new InvalidStatusTransitionException($locked->status, $target);
            }

            $locked->forceFill(array_filter([
                'status' => $target,
                'role' => $role,
            ]))->save();

            ($this->findPendingRequest($locked) ?? $this->openApprovalRequest($locked, Role::Guest, null))->forceFill([
                'status' => $target,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            return $locked;
        });
    }

    private function findPendingRequest(AdminUser $user): ?UserApprovalRequest
    {
        return $user->approvalRequests()
            ->where('status', UserStatus::Pending)
            ->latest('id')
            ->first();
    }

    private function openApprovalRequest(AdminUser $user, Role $requestedRole, ?string $note): UserApprovalRequest
    {
        return $user->approvalRequests()->create([
            'requested_role' => $requestedRole,
            'status' => UserStatus::Pending,
            'note' => $note,
        ]);
    }
}
