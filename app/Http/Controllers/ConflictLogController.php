<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CakismaLogu;
use Illuminate\Contracts\View\View;

class ConflictLogController extends Controller
{
    public function index(): View
    {
        return view('conflicts.index', [
            'kayitlar' => CakismaLogu::query()
                ->with(['akademikDonem', 'ogretimElemani', 'derslik', 'ders', 'user'])
                ->latest('id')
                ->paginate(25),
        ]);
    }
}
