<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            if (!Schema::hasColumn('channels', 'bot_schedule_type')) {
                $table->string('bot_schedule_type')->default('always')->after('is_bot_enabled');
            }
            if (!Schema::hasColumn('channels', 'office_start_time')) {
                $table->string('office_start_time')->default('08:00')->after('bot_schedule_type');
            }
            if (!Schema::hasColumn('channels', 'office_end_time')) {
                $table->string('office_end_time')->default('17:00')->after('office_start_time');
            }
            if (!Schema::hasColumn('channels', 'work_days')) {
                $table->json('work_days')->nullable()->after('office_end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['bot_schedule_type', 'office_start_time', 'office_end_time', 'work_days'] as $col) {
                if (Schema::hasColumn('channels', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
