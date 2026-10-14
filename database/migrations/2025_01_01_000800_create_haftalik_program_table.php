<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per 30-minute slot. "akademik_donem_id" and "ogretim_elemani_id"
        // are denormalised from ders_atamalari so the database can enforce that an
        // instructor is never booked twice in the same slot.
        Schema::create('haftalik_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ders_atama_id')->constrained('ders_atamalari')->cascadeOnDelete();
            $table->foreignId('akademik_donem_id')->constrained('akademik_donemler')->cascadeOnDelete();
            $table->foreignId('ogretim_elemani_id')->constrained('ogretim_elemanlari')->cascadeOnDelete();
            $table->foreignId('derslik_id')->constrained('derslikler')->cascadeOnDelete();
            $table->unsignedTinyInteger('gun');
            $table->char('baslangic_saati', 5);
            $table->char('bitis_saati', 5);
            $table->timestamps();

            $table->unique(['akademik_donem_id', 'ogretim_elemani_id', 'gun', 'baslangic_saati'], 'haftalik_program_ogretim_elemani_slot_unique');
            $table->unique(['ders_atama_id', 'gun', 'baslangic_saati'], 'haftalik_program_atama_slot_unique');
            $table->index(['akademik_donem_id', 'derslik_id', 'gun', 'baslangic_saati'], 'haftalik_program_derslik_slot_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haftalik_program');
    }
};
