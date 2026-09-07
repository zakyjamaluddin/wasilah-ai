<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header Sequence Follow-Up
        Schema::create('follow_up_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // Contoh: "Nurturing Paket Umroh Reguler"
            $table->enum('platform', ['all', 'whatsapp', 'facebook', 'instagram'])->default('all');
            $table->enum('trigger_event', ['on_new_lead', 'on_pipeline_change', 'manual_only'])->default('on_new_lead');
            $table->string('pipeline_trigger_stage')->nullable(); // Misal: saat jadi 'warm_prospect'

            // Aturan Pengaman
            $table->boolean('stop_on_reply')->default(true); // Otomatis jeda jika customer balas
            $table->boolean('stop_on_closing')->default(true); // Berhenti total jika status 'won'
            $table->boolean('only_work_hours')->default(true); // Hanya kirim jam 08:00 - 20:00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Langkah-Langkah (Step 1, Step 2 ... Step N)
        Schema::create('follow_up_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_sequence_id')->constrained()->cascadeOnDelete();
            $table->integer('step_order')->default(1); // 1, 2, 3, 4, dst
            $table->integer('delay_value')->default(1); // Angka jeda
            $table->enum('delay_unit', ['minutes', 'hours', 'days', 'weeks'])->default('days');
            $table->enum('content_type', ['template', 'ai_prompt'])->default('template');
            $table->text('message_template')->nullable(); // Teks dengan {{name}}
            $table->text('ai_prompt')->nullable(); // Instruksi untuk Gemini AI
            $table->string('media_url')->nullable(); // PDF Brosur, Foto Hotel, Video Testimoni
            $table->timestamps();
        });

        // 3. Pelacak Antrean per Customer (Enrollments)
        Schema::create('follow_up_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('follow_up_sequence_id')->constrained()->cascadeOnDelete();
            $table->integer('current_step_order')->default(1);
            $table->timestamp('next_scheduled_at')->nullable()->index();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_enrollments');
        Schema::dropIfExists('follow_up_steps');
        Schema::dropIfExists('follow_up_sequences');
    }
};
