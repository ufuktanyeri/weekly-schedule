<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\CakismaLogu;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use App\Models\Program;
use App\Models\UserApprovalRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_a_complete_conflict_free_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, AdminUser::query()->count());
        $this->assertSame(3, UserApprovalRequest::query()->count());
        $this->assertSame(4, Program::query()->count());
        $this->assertSame(2, AkademikDonem::query()->count());
        $this->assertSame(1, AkademikDonem::query()->aktif()->count());
        $this->assertSame(6, Derslik::query()->count());
        $this->assertSame(8, OgretimElemani::query()->count());
        $this->assertSame(16, Ders::query()->count());
        $this->assertSame(17, DersAtama::query()->count());
        $this->assertGreaterThan(100, HaftalikProgram::query()->count());
        $this->assertGreaterThan(0, CakismaLogu::query()->count());

        // No classroom hosts two different courses in the same slot.
        $classroomClashes = DB::table('haftalik_program as a')
            ->join('haftalik_program as b', function ($join): void {
                $join->on('a.akademik_donem_id', '=', 'b.akademik_donem_id')
                    ->on('a.derslik_id', '=', 'b.derslik_id')
                    ->on('a.gun', '=', 'b.gun')
                    ->on('a.baslangic_saati', '=', 'b.baslangic_saati')
                    ->whereColumn('a.id', '<', 'b.id');
            })
            ->join('ders_atamalari as da', 'da.id', '=', 'a.ders_atama_id')
            ->join('ders_atamalari as db', 'db.id', '=', 'b.ders_atama_id')
            ->whereColumn('da.ders_id', '!=', 'db.ders_id')
            ->count();
        $this->assertSame(0, $classroomClashes);

        // No slot falls into the lunch break.
        $this->assertSame(0, HaftalikProgram::query()->whereIn('baslangic_saati', ['12:30', '13:00', '13:30'])->count());

        // Every instructor stays within their weekly limit.
        foreach (OgretimElemani::all() as $oe) {
            $assigned = (int) DersAtama::query()->where('ogretim_elemani_id', $oe->id)->sum('haftalik_saat');
            $this->assertLessThanOrEqual($oe->haftalik_ders_limiti, $assigned, $oe->tamAd());
        }
    }
}
