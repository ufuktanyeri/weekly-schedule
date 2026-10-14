<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Gun;
use App\Exceptions\ScheduleConflictException;
use App\Http\Requests\ScheduleEntryRequest;
use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\DersAtama;
use App\Models\Derslik;
use App\Models\HaftalikProgram;
use App\Models\OgretimElemani;
use App\Models\Program;
use App\Services\ProgramService;
use App\Services\TimeSlotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(private readonly ProgramService $programs) {}

    public function index(Request $request, TimeSlotService $timeSlots): View
    {
        $donemler = AkademikDonem::query()->orderByDesc('baslangic_tarihi')->get();
        $donem = $request->filled('donem')
            ? $donemler->firstWhere('id', $request->integer('donem'))
            : ($donemler->firstWhere('aktif', true) ?? $donemler->first());

        $programId = $request->filled('program') ? $request->integer('program') : null;
        $ogretimElemaniId = $request->filled('ogretim_elemani') ? $request->integer('ogretim_elemani') : null;
        $derslikId = $request->filled('derslik') ? $request->integer('derslik') : null;

        return view('schedule.index', [
            'donem' => $donem,
            'donemler' => $donemler,
            'programlar' => Program::query()->orderBy('program_adi')->get(),
            'ogretimElemanlari' => OgretimElemani::query()->orderBy('ad')->orderBy('soyad')->get(),
            'derslikler' => Derslik::query()->orderBy('derslik_kodu')->get(),
            'atamalar' => $donem === null ? collect() : DersAtama::query()
                ->with(['ders', 'ogretimElemani'])
                ->where('akademik_donem_id', $donem->id)
                ->get(),
            'filtre' => ['program' => $programId, 'ogretim_elemani' => $ogretimElemaniId, 'derslik' => $derslikId],
            'gunler' => Gun::cases(),
            'slotlar' => $timeSlots->allSlots(),
            'timeSlots' => $timeSlots,
            'grid' => $donem === null ? [] : $this->programs->grid($donem, $programId, $ogretimElemaniId, $derslikId),
        ]);
    }

    public function store(ScheduleEntryRequest $request): RedirectResponse
    {
        $atama = DersAtama::query()->findOrFail($request->integer('ders_atama_id'));
        $derslik = Derslik::query()->findOrFail($request->integer('derslik_id'));
        $gun = $request->enum('gun', Gun::class) ?? Gun::Pazartesi;
        $actor = $request->user();

        try {
            $this->programs->schedule(
                $atama,
                $derslik,
                $gun,
                $request->string('baslangic_saati')->toString(),
                $request->string('bitis_saati')->toString(),
                $actor instanceof AdminUser ? $actor : null,
            );
        } catch (ScheduleConflictException $e) {
            return back()->withInput()->withErrors(['cakisma' => $e->messages()]);
        }

        return back()->with('status', 'Ders programa eklendi.');
    }

    public function destroy(HaftalikProgram $kayit): RedirectResponse
    {
        $this->programs->removeEntry($kayit);

        return back()->with('status', 'Program kaydı silindi.');
    }
}
