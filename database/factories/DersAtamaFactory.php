<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AkademikDonem;
use App\Models\Ders;
use App\Models\DersAtama;
use App\Models\OgretimElemani;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DersAtama>
 */
class DersAtamaFactory extends Factory
{
    protected $model = DersAtama::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'akademik_donem_id' => AkademikDonem::factory(),
            'ders_id' => Ders::factory(),
            'ogretim_elemani_id' => OgretimElemani::factory(),
            'haftalik_saat' => 4,
            'sube' => 'A',
        ];
    }
}
