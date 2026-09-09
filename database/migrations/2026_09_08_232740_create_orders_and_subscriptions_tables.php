<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Tagihan / Order Pembayaran
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // INV-202609-XXXX
            $table->string('plan_code'); // starter, pro, custom
            $table->string('plan_name');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'expired', 'failed'])->default('pending');
            $table->string('payment_url')->nullable();
            $table->string('snap_token')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Data Pendaftaran Klien (Disimpan aman sementara sampai pembayaran lunas)
            $table->string('customer_name');
            $table->string('customer_email')->index();
            $table->string('customer_password'); // Hashed password
            $table->string('office_name');

            // Hasil Relasi Setelah Pembayaran Sukses
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // 2. Tabel Periode Langganan Aktif (Subscriptions)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_code');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at'); // 30 Hari ke depan
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('orders');
    }
};
