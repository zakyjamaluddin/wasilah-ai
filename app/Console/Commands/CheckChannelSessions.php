<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Services\ComposioService;
use Illuminate\Console\Command;

class CheckChannelSessions extends Command
{
    protected $signature = 'channels:check-sessions';
    protected $description = 'Memeriksa kesehatan sesi koneksi Facebook & Instagram di seluruh kantor';

    public function handle(ComposioService $composio)
    {
        $this->info('Memulai pengecekan sesi channel Meta...');

        $channels = Channel::with('office.composioAccount')
            ->whereIn('type', ['facebook', 'instagram'])
            ->where('status', 'connected')
            ->get();

        foreach ($channels as $channel) {
            $office = $channel->office;
            if (!$office || !$office->composioAccount) continue;

            $isHealthy = $composio->checkConnectionHealth($office, $channel);

            if ($isHealthy) {
                $this->info("✅ Channel [{$channel->name}] Kantor [{$office->name}] Aktif.");
            } else {
                $this->warn("⚠️ Channel [{$channel->name}] Kantor [{$office->name}] Terputus / Kedaluwarsa.");
            }
        }

        $this->info('Pengecekan sesi selesai.');
        return Command::SUCCESS;
    }
}
