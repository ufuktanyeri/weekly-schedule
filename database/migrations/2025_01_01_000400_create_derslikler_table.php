<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derslikler', function (Blueprint $table) {
            $table->id();
            $table->string('derslik_kodu', 20)->unique();
            $table->string('derslik_adi', 100);
            $table->unsignedSmallInteger('kapasite');
            $table->enum('tur', ['sinif', 'amfi', 'laboratuvar', 'bilgisayar_lab', 'atolye'])->default('sinif');
            $table->string('bina', 100)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derslikler');
    }
};
