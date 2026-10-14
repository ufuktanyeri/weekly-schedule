<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ogretim_elemanlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->unique()->constrained('admin_users')->nullOnDelete();
            $table->string('unvan', 50)->nullable();
            $table->string('ad', 100);
            $table->string('soyad', 100);
            $table->string('eposta')->nullable()->unique();
            $table->char('renk_kodu', 7)->default('#3B82F6');
            $table->enum('desen', ['none', 'dots', 'stripes', 'grid', 'diagonal'])->default('none');
            $table->unsignedTinyInteger('haftalik_ders_limiti')->default(20);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ogretim_elemanlari');
    }
};
