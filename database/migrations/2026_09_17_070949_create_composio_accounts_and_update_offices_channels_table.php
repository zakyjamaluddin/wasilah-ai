<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi ke database.
     */
    public function up(): void
    {
        // 1. Membuat tabel baru 'composio_accounts' untuk menyimpan banyak token Composio
        Schema::create('composio_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "Akun Composio 1 (zaky@email.com)"
            $table->text('api_key'); // API Key rahasia dari dashboard composio.dev
            $table->integer('max_offices')->default(4); // Maksimal 4 kantor per token
            $table->boolean('is_active')->default(true); // Status saklar aktif/nonaktif
            $table->text('notes')->nullable(); // Catatan pengingat
            $table->timestamps();
        });

        // 2. Menambahkan kolom 'composio_account_id' ke tabel 'offices'
        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('composio_account_id')
                ->nullable()
                ->after('id')
                ->constrained('composio_accounts')
                ->nullOnDelete();
        });

        // 3. Menambahkan kolom pelacak Composio ke tabel 'channels'
        Schema::table('channels', function (Blueprint $table) {
            $table->string('composio_entity_id')->nullable()->after('identifier');
            $table->string('composio_connection_id')->nullable()->after('composio_entity_id');
        });
    }

    /**
     * Kembalikan perubahan jika migrasi di-rollback.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['composio_entity_id', 'composio_connection_id']);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropForeign(['composio_account_id']);
            $table->dropColumn('composio_account_id');
        });

        Schema::dropIfExists('composio_accounts');
    }
};