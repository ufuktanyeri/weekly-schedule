<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ScheduleConflictException;
use App\Http\Requests\AssignmentRequest;
use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\OgretimElemani;
use App\Services\ProgramService;
use App\Services\WorkloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(private readonly ProgramService $programs) {}

    public function index(Request $request, WorkloadService $workload): View
    {
        $donemler = AkademikDonem::query()->orderByDesc('baslangic_tarihi')->get();
        $donem = $request->filled('donem')
            ? $donemler->firstWhere('id', $request->integer('donem'))
            : ($donemler->firstWhere('aktif', true) ?? $donemler->first());

        return view('assignments.index', [
            'donem' => $donem,
            'donemler' => $donemler,
            'dersler' => Ders::query()->with('program')->orderBy('ders_kodu')->get(),
            'ogretimElemanlari' => OgretimElemani::query()->where('aktif', true)->orderBy('ad')->get(),
            'atamalar' => $donem === null ? collect() : DersAtama::query()
                ->with(['ders.program', 'ogretimElemani'])
                ->where('akademik_donem_id', $donem->id)
                ->orderBy('id')
                ->get(),
            'isYukleri' => $donem === null ? [] : $workload->summary($donem),
        ]);
    }

    public function store(AssignmentRequest $request): RedirectResponse
    {
        $actor = $request->user();

        try {
            $this->programs->assignCourse(
                AkademikDonem::query()->findOrFail($request->integer('akademik_donem_id')),
                Ders::query()->findOrFail($request->integer('ders_id')),
                OgretimElemani::query()->findOrFail($request->integer('ogretim_elemani_id')),
                $request->filled('haftalik_saat') ? $request->integer('haftalik_saat') : null,
                $request->filled('sube') ? $request->string('sube')->upper()->toString() : 'A',
                $actor instanceof AdminUser ? $actor : null,
            );
        } catch (ScheduleConflictException $e) {
            return back()->withInput()->withErrors(['cakisma' => $e->messages()]);
        }

        return back()->with('status', 'Ders ataması oluşturuldu.');
    }

    public function destroy(DersAtama $atama): RedirectResponse
    {
        $atama->delete();

        return back()->with('status', 'Ders ataması silindi.');
    }
}
