<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\CakismaTuru;
use App\Enums\Gun;
use App\Exceptions\ScheduleConflictException;
use App\Models\AkademikDonem;
use App\Models\CakismaLogu;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use App\Services\ProgramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgramService $service;

    private AkademikDonem $donem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ProgramService::class);
        $this->donem = AkademikDonem::factory()->create();
    }

    private function atama(?OgretimElemani $oe = null, ?Ders $ders = null, string $sube = 'A'): DersAtama
    {
        return DersAtama::factory()->create([
            'akademik_donem_id' => $this->donem->id,
            'ogretim_elemani_id' => ($oe ?? OgretimElemani::factory()->create())->id,
            'ders_id' => ($ders ?? Ders::factory()->create())->id,
            'sube' => $sube,
        ]);
    }

    /**
     * @return list<CakismaTuru>
     */
    private function conflictTypes(callable $callback): array
    {
        try {
            $callback();
        } catch (ScheduleConflictException $e) {
            return array_values(array_unique(array_map(fn ($c) => $c->tur, $e->conflicts), SORT_REGULAR));
        }

        $this->fail('Expected a ScheduleConflictException.');
    }

    public function test_schedules_a_block_as_30_minute_slots(): void
    {
        $atama = $this->atama();
        $derslik = Derslik::factory()->create();

        $kayitlar = $this->service->schedule($atama, $derslik, Gun::Pazartesi, '08:30', '10:30');

        $this->assertCount(4, $kayitlar);
        $this->assertSame(['08:30', '09:00', '09:30', '10:00'], $kayitlar->pluck('baslangic_saati')->all());
        $this->assertDatabaseHas('haftalik_program', [
            'ders_atama_id' => $atama->id,
            'ogretim_elemani_id' => $atama->ogretim_elemani_id,
            'akademik_donem_id' => $this->donem->id,
            'gun' => Gun::Pazartesi->value,
            'baslangic_saati' => '10:00',
            'bitis_saati' => '10:30',
        ]);
    }

    public function test_rejects_instructor_double_booking_and_logs_it(): void
    {
        $oe = OgretimElemani::factory()->create();
        $this->service->schedule($this->atama($oe), Derslik::factory()->create(), Gun::Sali, '09:00', '10:00');

        $ikinci = $this->atama($oe);
        $types = $this->conflictTypes(fn () => $this->service->schedule($ikinci, Derslik::factory()->create(), Gun::Sali, '09:30', '10:30'));

        $this->assertSame([CakismaTuru::OgretimElemani], $types);
        $this->assertSame(0, HaftalikProgram::query()->where('ders_atama_id', $ikinci->id)->count());

        $log = CakismaLogu::query()->sole();
        $this->assertSame(CakismaTuru::OgretimElemani, $log->cakisma_turu);
        $this->assertSame(Gun::Sali, $log->gun);
        $this->assertSame('09:30', $log->baslangic_saati);
        $this->assertSame($ikinci->id, $log->ders_atama_id);
        $this->assertSame($oe->id, $log->ogretim_elemani_id);
        $this->assertNotNull($log->cakisan_kayit_id);
    }

    public function test_rejects_classroom_overlap_between_different_courses(): void
    {
        $derslik = Derslik::factory()->create();
        $this->service->schedule($this->atama(), $derslik, Gun::Carsamba, '14:00', '15:00');

        $types = $this->conflictTypes(fn () => $this->service->schedule($this->atama(), $derslik, Gun::Carsamba, '14:30', '15:30'));

        $this->assertSame([CakismaTuru::Derslik], $types);
        $this->assertSame(1, CakismaLogu::query()->where('cakisma_turu', CakismaTuru::Derslik)->count());
    }

    public function test_same_course_sections_may_share_a_classroom(): void
    {
        $ders = Ders::factory()->create();
        $derslik = Derslik::factory()->create();

        $this->service->schedule($this->atama(null, $ders, 'A'), $derslik, Gun::Persembe, '09:00', '10:00');
        $this->service->schedule($this->atama(null, $ders, 'B'), $derslik, Gun::Persembe, '09:00', '10:00');

        $this->assertSame(4, HaftalikProgram::query()->count());
    }

    public function test_rejects_scheduling_the_same_allocation_twice(): void
    {
        $atama = $this->atama();
        $derslik = Derslik::factory()->create();
        $this->service->schedule($atama, $derslik, Gun::Cuma, '09:00', '10:00');

        $types = $this->conflictTypes(fn () => $this->service->schedule($atama, Derslik::factory()->create(), Gun::Cuma, '09:30', '10:00'));

        $this->assertSame([CakismaTuru::MukerrerKayit], $types);
    }

    public function test_rejects_lunch_break_and_off_grid_times(): void
    {
        $atama = $this->atama();
        $derslik = Derslik::factory()->create();

        $this->assertSame([CakismaTuru::OgleArasi], $this->conflictTypes(
            fn () => $this->service->schedule($atama, $derslik, Gun::Pazartesi, '12:00', '13:00'),
        ));
        $this->assertSame([CakismaTuru::GecersizSaat], $this->conflictTypes(
            fn () => $this->service->schedule($atama, $derslik, Gun::Pazartesi, '09:15', '10:00'),
        ));
        $this->assertSame([CakismaTuru::GecersizSaat], $this->conflictTypes(
            fn () => $this->service->schedule($atama, $derslik, Gun::Pazartesi, '17:30', '18:30'),
        ));
        $this->assertSame(0, HaftalikProgram::query()->count());
        $this->assertSame(3, CakismaLogu::query()->count());
    }

    public function test_lunch_break_can_be_disabled_through_configuration(): void
    {
        config(['schedule.lunch_break.enabled' => false]);
        $service = $this->app->make(ProgramService::class);

        $kayitlar = $service->schedule($this->atama(), Derslik::factory()->create(), Gun::Pazartesi, '12:30', '13:30');

        $this->assertCount(2, $kayitlar);
    }

    public function test_conflicting_block_is_all_or_nothing(): void
    {
        $derslik = Derslik::factory()->create();
        $this->service->schedule($this->atama(), $derslik, Gun::Sali, '11:00', '11:30');

        $atama = $this->atama();
        $this->conflictTypes(fn () => $this->service->schedule($atama, $derslik, Gun::Sali, '09:00', '12:00'));

        $this->assertSame(0, HaftalikProgram::query()->where('ders_atama_id', $atama->id)->count());
    }

    public function test_terms_do_not_conflict_with_each_other(): void
    {
        $oe = OgretimElemani::factory()->create();
        $derslik = Derslik::factory()->create();
        $this->service->schedule($this->atama($oe), $derslik, Gun::Sali, '09:00', '10:00');

        $digerDonem = AkademikDonem::factory()->create();
        $digerAtama = DersAtama::factory()->create([
            'akademik_donem_id' => $digerDonem->id,
            'ogretim_elemani_id' => $oe->id,
        ]);

        $this->assertCount(2, $this->service->schedule($digerAtama, $derslik, Gun::Sali, '09:00', '10:00'));
    }

    public function test_scheduling_cannot_exceed_weekly_workload_limit(): void
    {
        $oe = OgretimElemani::factory()->create(['haftalik_ders_limiti' => 2]);
        $derslik = Derslik::factory()->create();
        $this->service->schedule($this->atama($oe), $derslik, Gun::Pazartesi, '08:30', '10:30');

        $types = $this->conflictTypes(fn () => $this->service->schedule($this->atama($oe), $derslik, Gun::Sali, '08:30', '09:00'));

        $this->assertSame([CakismaTuru::IsYuku], $types);
    }

    public function test_assignment_respects_default_20_hour_limit(): void
    {
        $oe = OgretimElemani::factory()->create();
        $this->assertSame(20, $oe->haftalik_ders_limiti);

        foreach (range(1, 5) as $i) {
            $this->service->assignCourse($this->donem, Ders::factory()->create(), $oe, 4);
        }

        $ders = Ders::factory()->create();
        $types = $this->conflictTypes(fn () => $this->service->assignCourse($this->donem, $ders, $oe, 1));

        $this->assertSame([CakismaTuru::IsYuku], $types);
        $this->assertSame(5, DersAtama::query()->count());
        $this->assertDatabaseHas('cakisma_loglari', [
            'cakisma_turu' => CakismaTuru::IsYuku->value,
            'ders_id' => $ders->id,
            'ogretim_elemani_id' => $oe->id,
            'akademik_donem_id' => $this->donem->id,
        ]);
    }

    public function test_assignment_defaults_to_course_hours_and_rejects_duplicates(): void
    {
        $oe = OgretimElemani::factory()->create();
        $ders = Ders::factory()->create(['teorik_saat' => 3, 'uygulama_saat' => 1]);

        $atama = $this->service->assignCourse($this->donem, $ders, $oe);
        $this->assertSame(4, $atama->haftalik_saat);

        $types = $this->conflictTypes(fn () => $this->service->assignCourse($this->donem, $ders, $oe));
        $this->assertSame([CakismaTuru::MukerrerKayit], $types);
    }

    public function test_grid_groups_entries_by_day_and_slot_with_filters(): void
    {
        $atama = $this->atama();
        $this->service->schedule($atama, Derslik::factory()->create(), Gun::Cuma, '14:00', '15:00');
        $this->service->schedule($this->atama(), Derslik::factory()->create(), Gun::Cuma, '14:00', '14:30');

        $grid = $this->service->grid($this->donem);
        $this->assertCount(5, $grid);
        $this->assertCount(19, $grid[Gun::Cuma->value]);
        $this->assertCount(2, $grid[Gun::Cuma->value]['14:00']);
        $this->assertCount(1, $grid[Gun::Cuma->value]['14:30']);

        $filtered = $this->service->grid($this->donem, ogretimElemaniId: $atama->ogretim_elemani_id);
        $this->assertCount(1, $filtered[Gun::Cuma->value]['14:00']);
    }
}
