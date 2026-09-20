<?php

use App\Services\FollowUpEngineService;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(FollowUpEngineService::class)->processDueEnrollments();
})->everyMinute();

// 🔥 2. JALANKAN PEMERIKSAAN KONEKSI WHATSAPP SETIAP 5 MENIT
Schedule::command('whatsapp:check-connections')
    ->everyFiveMinutes()
    ->runInBackground();
