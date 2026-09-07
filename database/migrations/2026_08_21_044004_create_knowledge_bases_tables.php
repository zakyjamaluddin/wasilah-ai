<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['all', 'whatsapp', 'facebook', 'instagram'])->default('all');
            $table->enum('type', ['text_doc', 'faq', 'dynamic_url'])->default('text_doc');

            $table->string('title');
            $table->longText('content')->nullable(); // Teks materi pengetahuan
            $table->string('source_url')->nullable(); // URL jika menggunakan dynamic scraping
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable(); // Kapan terakhir URL di-scrape ulang
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
