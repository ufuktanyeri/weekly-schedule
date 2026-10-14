<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enums\CakismaTuru;
use App\Enums\Role;
use App\Models\AdminUser;
use App\Models\CakismaLogu;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->editor = AdminUser::query()->where('email', 'editor@ankara.edu.tr')->sole();
    }

    public function test_pages_render_with_seeded_data(): void
    {
        $this->actingAs($this->editor)->get('/dashboard')->assertOk()->assertSee('2025-2026 Güz')->assertSee('Haftalık Ders Yükleri');
        $this->actingAs($this->editor)->get('/program')->assertOk()->assertSee('BP101')->assertSee('Öğle arası')->assertSee('12:30-13:00');
        $this->actingAs($this->editor)->get('/program?ogretim_elemani=1')->assertOk();
        $this->actingAs($this->editor)->get('/atamalar')->assertOk()->assertSee('Algoritma ve Programlama');
        $this->actingAs($this->editor)->get('/cakismalar')->assertOk()->assertSee('Derslik çakışması');

        $superAdmin = AdminUser::query()->where('email', 'super@ankara.edu.tr')->sole();
        $this->actingAs($superAdmin)->get('/kullanici-onaylari')->assertOk()->assertSee('aday.ogretmen@example.com');
    }

    public function test_conflicting_entry_is_rejected_with_message_and_logged(): void
    {
        $beforeId = (int) CakismaLogu::query()->max('id');
        $atama = DersAtama::query()->whereHas('ders', fn ($q) => $q->where('ders_kodu', 'MV105'))->sole();
        $blab = Derslik::query()->where('derslik_kodu', 'BLAB1')->sole();

        $this->actingAs($this->editor)
            ->from('/program')
            ->post('/program', [
                'ders_atama_id' => $atama->id,
                'derslik_id' => $blab->id,
                'gun' => 1,
                'baslangic_saati' => '08:30',
                'bitis_saati' => '09:30',
            ])
            ->assertRedirect('/program')
            ->assertSessionHasErrors('cakisma');

        // BLAB1 is busy in both 30-minute slots and Zeynep Çelik already teaches at 09:00.
        $logs = CakismaLogu::query()->where('id', '>', $beforeId)->orderBy('id')->get();
        $this->assertSame(
            [CakismaTuru::Derslik, CakismaTuru::OgretimElemani, CakismaTuru::Derslik],
            $logs->map(fn (CakismaLogu $log) => $log->cakisma_turu)->all(),
        );
        $this->assertTrue($logs->every(fn (CakismaLogu $log) => $log->admin_user_id === $this->editor->id && $log->mesaj !== ''));
        $this->assertSame(0, HaftalikProgram::query()->where('ders_atama_id', $atama->id)->where('gun', 1)->count());
    }

    public function test_valid_entry_is_added_and_can_be_removed(): void
    {
        $atama = DersAtama::query()->whereHas('ders', fn ($q) => $q->where('ders_kodu', 'MV105'))->sole();
        $d102 = Derslik::query()->where('derslik_kodu', 'D102')->sole();

        $this->actingAs($this->editor)->post('/program', [
            'ders_atama_id' => $atama->id,
            'derslik_id' => $d102->id,
            'gun' => 5,
            'baslangic_saati' => '09:00',
            'bitis_saati' => '10:00',
        ])->assertSessionHas('status')->assertSessionHasNoErrors();

        $kayit = HaftalikProgram::query()->where('ders_atama_id', $atama->id)->where('gun', 5)->firstOrFail();
        $this->actingAs($this->editor)->delete("/program/{$kayit->id}")->assertSessionHas('status');
        $this->assertModelMissing($kayit);
    }

    public function test_schedule_request_is_validated(): void
    {
        $this->actingAs($this->editor)->post('/program', [
            'ders_atama_id' => 999,
            'derslik_id' => 999,
            'gun' => 6,
            'baslangic_saati' => '25:00',
            'bitis_saati' => 'x',
        ])->assertSessionHasErrors(['ders_atama_id', 'derslik_id', 'gun', 'baslangic_saati', 'bitis_saati']);
    }

    public function test_assignment_over_workload_limit_is_rejected(): void
    {
        $fatma = OgretimElemani::query()->where('eposta', 'fatma.koc@ankara.edu.tr')->sole();
        $ders = Ders::query()->where('ders_kodu', 'GT105')->sole();

        $this->actingAs($this->editor)->post('/atamalar', [
            'akademik_donem_id' => 1,
            'ders_id' => $ders->id,
            'ogretim_elemani_id' => $fatma->id,
            'haftalik_saat' => 10,
            'sube' => 'C',
        ])->assertSessionHasErrors('cakisma');

        $this->assertDatabaseMissing('ders_atamalari', ['ogretim_elemani_id' => $fatma->id, 'sube' => 'C']);
    }

    public function test_guest_role_users_cannot_modify_schedule(): void
    {
        $guest = AdminUser::factory()->role(Role::Guest)->create();

        $this->actingAs($guest)->post('/program', [])->assertForbidden();
        $this->actingAs($guest)->delete('/program/1')->assertForbidden();
    }
}
