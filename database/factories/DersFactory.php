<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ders;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ders>
 */
class DersFactory extends Factory
{
    protected $model = Ders::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'ders_kodu' => fake()->unique()->bothify('DRS###'),
            'ders_adi' => 'Ders '.fake()->numerify('###'),
            'sinif' => 1,
            'teorik_saat' => 2,
            'uygulama_saat' => 2,
            'kredi' => 3,
            'akts' => 4,
            'aktif' => true,
        ];
    }
}
