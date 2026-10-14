<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ders_atamalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akademik_donem_id')->constrained('akademik_donemler')->cascadeOnDelete();
            $table->foreignId('ders_id')->constrained('dersler')->cascadeOnDelete();
            $table->foreignId('ogretim_elemani_id')->constrained('ogretim_elemanlari')->cascadeOnDelete();
            $table->unsignedTinyInteger('haftalik_saat');
            $table->string('sube', 10)->default('A');
            $table->timestamps();

            $table->unique(['akademik_donem_id', 'ders_id', 'ogretim_elemani_id', 'sube'], 'ders_atamalari_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ders_atamalari');
    }
};
