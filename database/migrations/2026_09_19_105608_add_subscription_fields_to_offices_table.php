<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi penambahan kolom subscription ke tabel offices.
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            // Status langganan: 'free', 'active', 'expiring', 'inactive'
            $table->string('subscription_status')->default('free')->after('is_active');

            // Tanggal kedaluwarsa langganan
            $table->timestamp('expired_at')->nullable()->after('subscription_status');
        });
    }

    /**
     * Rollback migrasi jika diperlukan.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'expired_at']);
        });
    }
};
