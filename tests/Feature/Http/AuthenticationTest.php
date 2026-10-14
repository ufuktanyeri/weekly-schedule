<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Google ile Giriş Yap');
        $this->get('/register')->assertOk();
    }

    public function test_registration_creates_pending_account_that_cannot_log_in(): void
    {
        $this->post('/register', [
            'name' => 'Yeni Kullanıcı',
            'email' => 'yeni@example.com',
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
            'requested_role' => 'instructor',
        ])->assertRedirect('/login')->assertSessionHas('status');

        $user = AdminUser::query()->where('email', 'yeni@example.com')->sole();
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame(Role::Guest, $user->role);

        $this->post('/login', ['email' => 'yeni@example.com', 'password' => 'secret-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_cannot_request_privileged_roles(): void
    {
        $this->post('/register', [
            'name' => 'Kötü Niyetli',
            'email' => 'kotu@example.com',
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
            'requested_role' => 'super_admin',
        ])->assertSessionHasErrors('requested_role');

        $this->assertDatabaseMissing('admin_users', ['email' => 'kotu@example.com']);
    }

    public function test_email_with_line_breaks_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'CRLF',
            'email' => "crlf@example.com\r\nBcc: victim@example.com",
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ])->assertSessionHasErrors('email');

        $this->post('/login', ['email' => "a@example.com\nX", 'password' => 'x'])
            ->assertSessionHasErrors('email');
    }

    public function test_approved_user_can_log_in_and_out(): void
    {
        $user = AdminUser::factory()->role(Role::Editor)->create(['email' => 'editor@example.com']);

        $this->post('/login', ['email' => 'editor@example.com', 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_session_of_a_no_longer_approved_user_is_terminated(): void
    {
        $user = AdminUser::factory()->rejected()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_google_callback_auto_grants_super_admin_for_verified_domain(): void
    {
        $this->mockGoogleUser('g-42', 'dekan@ankara.edu.tr', true);

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $user = AdminUser::query()->where('email', 'dekan@ankara.edu.tr')->sole();
        $this->assertSame(Role::SuperAdmin, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_callback_for_other_domains_waits_for_approval(): void
    {
        $this->mockGoogleUser('g-43', 'someone@gmail.com', true);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHas('status');

        $this->assertSame(UserStatus::Pending, AdminUser::query()->where('email', 'someone@gmail.com')->sole()->status);
        $this->assertGuest();
    }

    private function mockGoogleUser(string $id, string $email, bool $verified): void
    {
        $googleUser = (new SocialiteUser)
            ->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $verified])
            ->map(['id' => $id, 'email' => $email, 'name' => 'Google Kullanıcı', 'avatar' => null]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
