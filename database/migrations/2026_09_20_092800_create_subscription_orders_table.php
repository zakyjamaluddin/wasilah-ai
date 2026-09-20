<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // Contoh: ORD-202609-XXXX
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->string('customer_email')->index(); // Email user (Kunci pencocokan Lynk.id)
            $table->string('plan_type'); // '1_month' atau '1_year'
            $table->decimal('amount', 12, 2); // 37000 atau 300000
            $table->string('status')->default('pending'); // 'pending', 'paid', 'expired'
            $table->string('payment_provider')->default('lynkid');
            $table->timestamp('paid_at')->nullable();
            $table->json('payment_metadata')->nullable(); // Simpan salinan isi email Lynk.id
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
