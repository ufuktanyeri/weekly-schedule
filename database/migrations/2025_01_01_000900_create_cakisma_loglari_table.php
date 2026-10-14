<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cakisma_loglari', function (Blueprint $table) {
            $table->id();
            $table->enum('cakisma_turu', ['ogretim_elemani', 'derslik', 'mukerrer_kayit', 'is_yuku', 'gecersiz_saat', 'ogle_arasi'])->index();
            $table->foreignId('akademik_donem_id')->nullable()->constrained('akademik_donemler')->nullOnDelete();
            $table->foreignId('ders_atama_id')->nullable()->constrained('ders_atamalari')->nullOnDelete();
            $table->foreignId('ders_id')->nullable()->constrained('dersler')->nullOnDelete();
            $table->foreignId('ogretim_elemani_id')->nullable()->constrained('ogretim_elemanlari')->nullOnDelete();
            $table->foreignId('derslik_id')->nullable()->constrained('derslikler')->nullOnDelete();
            $table->foreignId('cakisan_kayit_id')->nullable()->constrained('haftalik_program')->nullOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->unsignedTinyInteger('gun')->nullable();
            $table->char('baslangic_saati', 5)->nullable();
            $table->char('bitis_saati', 5)->nullable();
            $table->text('mesaj');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cakisma_loglari');
    }
};
