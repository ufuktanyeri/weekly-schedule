<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DonemTuru;
use App\Models\AkademikDonem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AkademikDonem>
 */
class AkademikDonemFactory extends Factory
{
    protected $model = AkademikDonem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $yil = fake()->unique()->numberBetween(2000, 2099);

        return [
            'egitim_yili' => $yil.'-'.($yil + 1),
            'donem' => DonemTuru::Guz,
            'baslangic_tarihi' => $yil.'-09-15',
            'bitis_tarihi' => ($yil + 1).'-01-15',
            'aktif' => true,
        ];
    }
}
