<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\AuthProvider;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\AuthenticationFailedException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\AdminUser;
use App\Services\AuthService;
use App\Support\GoogleUserData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = $this->app->make(AuthService::class);
    }

    private function google(string $email, bool $verified = true, string $id = 'g-1'): GoogleUserData
    {
        return new GoogleUserData($id, $email, 'Google Kullanıcı', $verified, 'https://example.com/a.png');
    }

    public function test_registration_starts_pending_as_guest_with_an_approval_request(): void
    {
        $user = $this->auth->register('Ali Veli', 'Ali@Example.com', 'secret-123', Role::Teacher, 'Not');

        $this->assertSame('ali@example.com', $user->email);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame(Role::Guest, $user->role);
        $this->assertSame(AuthProvider::Local, $user->auth_provider);
        $this->assertTrue(Hash::check('secret-123', (string) $user->password));

        $request = $user->approvalRequests()->sole();
        $this->assertSame(Role::Teacher, $request->requested_role);
        $this->assertSame(UserStatus::Pending, $request->status);
    }

    public function test_local_registration_with_super_admin_domain_is_not_escalated(): void
    {
        $user = $this->auth->register('Sahte', 'sahte@ankara.edu.tr', 'secret-123');

        $this->assertSame(Role::Guest, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
    }

    public function test_only_approved_users_can_log_in(): void
    {
        $this->auth->register('Ali', 'ali@example.com', 'secret-123');

        try {
            $this->auth->login('ali@example.com', 'secret-123');
            $this->fail('Pending user must not log in.');
        } catch (AuthenticationFailedException $e) {
            $this->assertStringContainsString('onay', $e->getMessage());
        }
        $this->assertGuest();

        AdminUser::factory()->rejected()->create(['email' => 'red@example.com']);
        $this->expectException(AuthenticationFailedException::class);
        $this->auth->login('red@example.com', 'password');
    }

    public function test_approved_user_logs_in_and_wrong_password_fails(): void
    {
        $user = AdminUser::factory()->create(['email' => 'onayli@example.com']);

        $this->auth->login('ONAYLI@example.com', 'password');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->last_login_at);

        $this->auth->logout();
        $this->expectException(AuthenticationFailedException::class);
        $this->auth->login('onayli@example.com', 'wrong');
    }

    public function test_admin_approves_pending_user_with_requested_role(): void
    {
        $admin = AdminUser::factory()->role(Role::Admin)->create();
        $user = $this->auth->register('Ali', 'ali@example.com', 'secret-123', Role::Instructor);

        $approved = $this->auth->approve($user, $admin, note: 'Uygun');

        $this->assertSame(UserStatus::Approved, $approved->status);
        $this->assertSame(Role::Instructor, $approved->role);

        $request = $user->approvalRequests()->sole();
        $this->assertSame(UserStatus::Approved, $request->status);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertSame('Uygun', $request->review_note);
        $this->assertNotNull($request->reviewed_at);
    }

    public function test_admin_cannot_grant_super_admin_or_admin(): void
    {
        $admin = AdminUser::factory()->role(Role::Admin)->create();
        $user = $this->auth->register('Ali', 'ali@example.com', 'secret-123');

        try {
            $this->auth->approve($user, $admin, Role::SuperAdmin);
            $this->fail('Admin must not grant super_admin.');
        } catch (AuthorizationException) {
            $this->assertSame(UserStatus::Pending, $user->fresh()?->status);
        }

        $superAdmin = AdminUser::factory()->role(Role::SuperAdmin)->create();
        $this->assertSame(Role::Admin, $this->auth->approve($user, $superAdmin, Role::Admin)->role);
    }

    public function test_non_admin_roles_cannot_review_registrations(): void
    {
        $editor = AdminUser::factory()->role(Role::Editor)->create();
        $user = $this->auth->register('Ali', 'ali@example.com', 'secret-123');

        $this->expectException(AuthorizationException::class);
        $this->auth->reject($user, $editor);
    }

    public function test_rejection_is_final(): void
    {
        $admin = AdminUser::factory()->role(Role::Admin)->create();
        $user = $this->auth->register('Ali', 'ali@example.com', 'secret-123');

        $rejected = $this->auth->reject($user, $admin, 'Kurum dışı');
        $this->assertSame(UserStatus::Rejected, $rejected->status);
        $this->assertSame('Kurum dışı', $user->approvalRequests()->sole()->review_note);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->auth->approve($user, $admin, Role::Guest);
    }

    public function test_verified_google_login_on_super_admin_domain_is_auto_granted(): void
    {
        $user = $this->auth->loginWithGoogle($this->google('Rektor@Ankara.edu.tr'));

        $this->assertSame(Role::SuperAdmin, $user->role);
        $this->assertSame(UserStatus::Approved, $user->status);
        $this->assertSame(AuthProvider::Google, $user->auth_provider);
        $this->assertSame('g-1', $user->google_id);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_outside_domain_or_on_subdomain_stays_pending(): void
    {
        $user = $this->auth->loginWithGoogle($this->google('ogrenci@ogr.ankara.edu.tr'));

        $this->assertSame(Role::Guest, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame(1, $user->approvalRequests()->count());
        $this->assertGuest();

        // Logging in again while pending does not duplicate the approval request.
        $this->auth->loginWithGoogle($this->google('ogrenci@ogr.ankara.edu.tr'));
        $this->assertSame(1, $user->approvalRequests()->count());
    }

    public function test_unverified_google_email_is_refused(): void
    {
        $this->expectException(AuthenticationFailedException::class);

        $this->auth->loginWithGoogle($this->google('rektor@ankara.edu.tr', verified: false));
    }

    public function test_google_login_links_existing_pending_account(): void
    {
        $local = $this->auth->register('Hoca', 'hoca@ankara.edu.tr', 'secret-123');

        $user = $this->auth->loginWithGoogle($this->google('hoca@ankara.edu.tr'));

        $this->assertSame($local->id, $user->id);
        $this->assertSame(Role::SuperAdmin, $user->role);
        $this->assertSame(UserStatus::Approved, $user->status);
        $this->assertSame(UserStatus::Approved, $local->approvalRequests()->sole()->status);
    }

    public function test_google_login_refuses_email_bound_to_another_google_account(): void
    {
        $this->auth->loginWithGoogle($this->google('hoca@ankara.edu.tr', id: 'g-1'));

        $this->expectException(AuthenticationFailedException::class);
        $this->auth->loginWithGoogle($this->google('hoca@ankara.edu.tr', id: 'g-2'));
    }

    public function test_super_admin_domain_matching(): void
    {
        config(['schedule.super_admin_domains' => ['ankara.edu.tr', '@example.org']]);

        $this->assertTrue($this->auth->isSuperAdminDomain('a@ANKARA.edu.tr'));
        $this->assertTrue($this->auth->isSuperAdminDomain('a@example.org'));
        $this->assertFalse($this->auth->isSuperAdminDomain('a@ogr.ankara.edu.tr'));
        $this->assertFalse($this->auth->isSuperAdminDomain('a@ankara.edu.tr.evil.com'));
        $this->assertFalse($this->auth->isSuperAdminDomain('not-an-email'));
    }
}
