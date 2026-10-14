<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akademik_donemler', function (Blueprint $table) {
            $table->id();
            $table->string('egitim_yili', 9);
            $table->enum('donem', ['guz', 'bahar', 'yaz']);
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi');
            $table->boolean('aktif')->default(false)->index();
            $table->timestamps();

            $table->unique(['egitim_yili', 'donem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akademik_donemler');
    }
};
