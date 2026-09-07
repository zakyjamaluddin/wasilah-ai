<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Kantor (Tenant)
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Pivot User <-> Office (Keanggotaan User di Kantor)
        Schema::create('office_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['office_id', 'user_id']);
        });

        // 3. Tabel Channel Koneksi (WA, FB, IG) per Kantor
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['whatsapp', 'facebook', 'instagram']);
            $table->string('name');
            $table->string('identifier')->nullable(); // SessionId WA atau Page ID FB/IG
            $table->json('credentials')->nullable();
            $table->enum('status', ['connected', 'disconnected', 'scanning'])->default('disconnected');
            $table->boolean('is_bot_enabled')->default(true);
            $table->json('bot_schedule')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
        Schema::dropIfExists('office_user');
        Schema::dropIfExists('offices');
    }
};
