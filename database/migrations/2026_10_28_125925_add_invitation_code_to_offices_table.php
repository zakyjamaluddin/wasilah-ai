<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('invitation_code', 64)->nullable()->unique()->after('slug');
        });

        // Generate invitation_code untuk seluruh kantor yang sudah ada
        $offices = DB::table('offices')->whereNull('invitation_code')->get();
        foreach ($offices as $office) {
            DB::table('offices')->where('id', $office->id)->update([
                'invitation_code' => Str::random(24),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('invitation_code');
        });
    }
};
