<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dersler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programlar')->cascadeOnDelete();
            $table->string('ders_kodu', 20);
            $table->string('ders_adi', 150);
            $table->unsignedTinyInteger('sinif')->default(1);
            $table->unsignedTinyInteger('teorik_saat')->default(0);
            $table->unsignedTinyInteger('uygulama_saat')->default(0);
            $table->unsignedTinyInteger('kredi')->default(0);
            $table->unsignedTinyInteger('akts')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'ders_kodu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dersler');
    }
};
