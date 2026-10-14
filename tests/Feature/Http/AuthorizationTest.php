<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_view_but_not_manage(): void
    {
        $instructor = AdminUser::factory()->role(Role::Instructor)->create();

        $this->actingAs($instructor)->get('/dashboard')->assertOk();
        $this->actingAs($instructor)->get('/program')->assertOk();
        $this->actingAs($instructor)->get('/atamalar')->assertForbidden();
        $this->actingAs($instructor)->get('/cakismalar')->assertForbidden();
        $this->actingAs($instructor)->post('/program', [])->assertForbidden();
        $this->actingAs($instructor)->get('/kullanici-onaylari')->assertForbidden();
    }

    public function test_editor_manages_schedule_but_not_users(): void
    {
        $editor = AdminUser::factory()->role(Role::Editor)->create();

        $this->actingAs($editor)->get('/atamalar')->assertOk();
        $this->actingAs($editor)->get('/cakismalar')->assertOk();
        $this->actingAs($editor)->get('/kullanici-onaylari')->assertForbidden();
    }

    public function test_admin_approves_and_rejects_through_the_ui(): void
    {
        $admin = AdminUser::factory()->role(Role::Admin)->create();
        $auth = $this->app->make(AuthService::class);
        $ali = $auth->register('Ali', 'ali@example.com', 'secret-123', Role::Teacher);
        $veli = $auth->register('Veli', 'veli@example.com', 'secret-123');

        $this->actingAs($admin)->get('/kullanici-onaylari')->assertOk()->assertSee('ali@example.com');

        $this->actingAs($admin)->post("/kullanici-onaylari/{$ali->id}/onayla", ['role' => 'teacher'])
            ->assertSessionHas('status');
        $this->actingAs($admin)->post("/kullanici-onaylari/{$veli->id}/reddet", ['reason' => 'Uygun değil'])
            ->assertSessionHas('status');

        $this->assertSame(UserStatus::Approved, $ali->refresh()->status);
        $this->assertSame(Role::Teacher, $ali->refresh()->role);
        $this->assertSame(UserStatus::Rejected, $veli->refresh()->status);
    }

    public function test_admin_cannot_escalate_via_the_ui(): void
    {
        $admin = AdminUser::factory()->role(Role::Admin)->create();
        $ali = $this->app->make(AuthService::class)->register('Ali', 'ali@example.com', 'secret-123');

        $this->actingAs($admin)->post("/kullanici-onaylari/{$ali->id}/onayla", ['role' => 'super_admin'])
            ->assertSessionHasErrors('onay');

        $this->assertSame(UserStatus::Pending, $ali->refresh()->status);
    }
}
