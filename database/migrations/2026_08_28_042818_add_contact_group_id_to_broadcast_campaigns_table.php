<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->foreignId('contact_group_id')->nullable()->after('channel_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropForeign(['contact_group_id']);
            $table->dropColumn('contact_group_id');
        });
    }
};
