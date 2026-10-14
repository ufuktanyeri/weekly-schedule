<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programlar', function (Blueprint $table) {
            $table->id();
            $table->string('program_kodu', 20)->unique();
            $table->string('program_adi', 150);
            $table->enum('derece', ['on_lisans', 'lisans'])->default('on_lisans');
            $table->string('bolum_adi', 150)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programlar');
    }
};
