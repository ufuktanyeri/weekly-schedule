<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DerslikTuru;
use App\Models\Derslik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Derslik>
 */
class DerslikFactory extends Factory
{
    protected $model = Derslik::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'derslik_kodu' => fake()->unique()->bothify('D-###'),
            'derslik_adi' => 'Derslik '.fake()->numberBetween(1, 999),
            'kapasite' => fake()->numberBetween(20, 120),
            'tur' => DerslikTuru::Sinif,
            'bina' => null,
            'aktif' => true,
        ];
    }
}
