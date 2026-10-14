<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a complete local development dataset.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ProgramSeeder::class,
            AkademikDonemSeeder::class,
            DerslikSeeder::class,
            OgretimElemaniSeeder::class,
            DersSeeder::class,
            DersAtamaSeeder::class,
            HaftalikProgramSeeder::class,
        ]);
    }
}
