<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Services\BaileysService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWhatsAppConnectionsCommand extends Command
{
    /**
     * Nama perintah artisan
     */
    protected $signature = 'whatsapp:check-connections';

    /**
     * Deskripsi perintah
     */
    protected $description = 'Memeriksa status koneksi seluruh saluran WhatsApp ke VPS Baileys secara berkala';

    public function handle(BaileysService $baileys)
    {
        $this->info("🔍 Memulai pemeriksaan status koneksi WhatsApp...");

        $channels = Channel::where('type', 'whatsapp')
            ->whereNotNull('identifier')
            ->with('office')
            ->get();

        if ($channels->isEmpty()) {
            $this->info("ℹ️ Tidak ada saluran WhatsApp yang terdaftar.");
            return 0;
        }

        $connectedCount = 0;
        $disconnectedCount = 0;

        foreach ($channels as $channel) {
            $sessionId = $channel->identifier;
            $previousStatus = $channel->status;

            try {
                // Tembak ke VPS Baileys untuk cek status soket
                $response = $baileys->getSessionStatus($sessionId);
                $vpsStatus = $response['data']['status'] ?? 'disconnected';

                $newStatus = ($vpsStatus === 'connected') ? 'connected' : 'disconnected';

                // Update status di database jika ada perubahan status koneksi
                if ($previousStatus !== $newStatus) {
                    $channel->update(['status' => $newStatus]);

                    if ($newStatus === 'disconnected') {
                        Log::warning("⚠️ [WA Disconnected] Saluran {$channel->name} di kantor {$channel->office?->name} terputus dari WhatsApp!");
                        $this->warn("   ❌ {$channel->name} ({$sessionId}): TERPUTUS / DISCONNECTED");
                    } else {
                        Log::info("✅ [WA Reconnected] Saluran {$channel->name} di kantor {$channel->office?->name} kembali terhubung.");
                        $this->info("   ✅ {$channel->name} ({$sessionId}): TERHUBUNG KEMBALI");
                    }
                } else {
                    $this->line("   • {$channel->name} ({$sessionId}): " . strtoupper($newStatus));
                }

                if ($newStatus === 'connected') {
                    $connectedCount++;
                } else {
                    $disconnectedCount++;
                }

            } catch (\Exception $e) {
                $channel->update(['status' => 'disconnected']);
                $disconnectedCount++;
                Log::error("[WA Health-Check Error] {$channel->name}: " . $e->getMessage());
                $this->error("   ❌ {$channel->name} ({$sessionId}): Error koneksi ke VPS");
            }
        }

        $this->info("\n📊 HASIL PEMERIKSAAN:");
        $this->info("   ✅ Tersambung (Online) : {$connectedCount}");
        $this->warn("   ❌ Terputus (Offline)  : {$disconnectedCount}");

        return 0;
    }
}
