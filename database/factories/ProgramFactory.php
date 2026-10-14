<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProgramDerecesi;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_kodu' => fake()->unique()->bothify('PRG-###'),
            'program_adi' => 'Program '.fake()->numerify('###'),
            'derece' => ProgramDerecesi::OnLisans,
            'bolum_adi' => null,
            'aktif' => true,
        ];
    }
}
