<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\AkademikDonem;
use App\Models\CakismaLogu;
use App\Models\Ders;
use App\Models\Derslik;
use App\Models\OgretimElemani;
use App\Services\WorkloadService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(WorkloadService $workload): View
    {
        $donem = AkademikDonem::query()->aktif()->latest('baslangic_tarihi')->first();

        return view('dashboard', [
            'donem' => $donem,
            'istatistikler' => [
                'Öğretim Elemanı' => OgretimElemani::query()->count(),
                'Ders' => Ders::query()->count(),
                'Derslik' => Derslik::query()->count(),
                'Onay Bekleyen' => AdminUser::query()->where('status', UserStatus::Pending)->count(),
                'Çakışma Kaydı' => CakismaLogu::query()->count(),
            ],
            'isYukleri' => $donem !== null ? $workload->summary($donem) : [],
        ]);
    }
}
