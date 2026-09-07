<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thread Percakapan (Omnichannel Room)
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->enum('channel_type', ['whatsapp', 'fb_dm', 'fb_comment', 'ig_dm', 'ig_comment']);
            $table->boolean('is_bot_active')->default(true); // Switch Bot vs CS Manual Takeover
            $table->integer('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        // 2. Detail Butir Pesan (Teks, Gambar, Audio, Dokumen)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Jika agen manusia yang balas

            $table->enum('sender_type', ['customer', 'bot', 'agent']);
            $table->enum('message_type', ['text', 'image', 'audio', 'document', 'video', 'location'])->default('text');
            $table->text('message_body')->nullable();
            $table->string('media_url')->nullable(); // Link file gambar/media lokal atau S3
            $table->text('ai_vision_summary')->nullable(); // Deskripsi hasil pembacaan gambar oleh AI
            $table->string('external_message_id')->nullable()->index(); // ID pesan WhatsApp/Meta
            $table->json('raw_payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
