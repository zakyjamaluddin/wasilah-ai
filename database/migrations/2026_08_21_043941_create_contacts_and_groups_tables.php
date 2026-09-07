<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Grup Kontak
        Schema::create('contact_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Data Kontak Leads
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number')->nullable()->index();
            $table->string('wa_jid')->nullable()->index(); // Phone JID atau @lid
            $table->string('fb_user_id')->nullable()->index();
            $table->string('ig_username')->nullable()->index();
            $table->string('email')->nullable();

            // CRM Pipeline Stage
            $table->enum('pipeline_stage', ['lead', 'cold_prospect', 'warm_prospect', 'hot_prospect', 'closing', 'won', 'lost'])->default('lead');
            $table->text('ai_summary')->nullable(); // Ringkasan otomatis kebutuhan leads oleh AI
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });

        // 3. Pivot Grup <-> Kontak (DENGAN PROTEKSI ANTI-DUPLIKASI)
        Schema::create('contact_group_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Kunci: Tidak boleh ada 2 kontak yang sama di dalam 1 grup kontak
            $table->unique(['contact_group_id', 'contact_id'], 'unique_contact_in_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_group_contact');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('contact_groups');
    }
};
