<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Desen;
use App\Models\OgretimElemani;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OgretimElemani>
 */
class OgretimElemaniFactory extends Factory
{
    protected $model = OgretimElemani::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unvan' => fake()->randomElement(['Öğr. Gör.', 'Dr. Öğr. Üyesi', 'Doç. Dr.', 'Prof. Dr.']),
            'ad' => fake()->firstName(),
            'soyad' => fake()->lastName(),
            'eposta' => fake()->unique()->safeEmail(),
            'renk_kodu' => strtoupper(fake()->hexColor()),
            'desen' => fake()->randomElement(Desen::cases()),
            'haftalik_ders_limiti' => 20,
            'aktif' => true,
        ];
    }
}
