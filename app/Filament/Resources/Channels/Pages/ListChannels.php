<?php

namespace App\Filament\Resources\Channels\Pages;

use App\Filament\Resources\Channels\ChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Services\ComposioService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class ListChannels extends ListRecords
{
    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

     /**
     * 🔥 DIEKSEKUSI OTOMATIS SAAT USER KEMBALI DARI OAUTH COMPOSIO
     */
    public function mount(): void
    {
        parent::mount();

        $currentOffice = Filament::getTenant();

        if ($currentOffice && $currentOffice->composio_account_id) {
            try {
                $composio = app(ComposioService::class);

                // Auto-Sync Facebook & Instagram di latar belakang
                $fbChannel = $composio->autoSyncOfficeChannel($currentOffice, 'facebook');
                $igChannel = $composio->autoSyncOfficeChannel($currentOffice, 'instagram');

                // Jika baru saja berhasil terhubung, beri notifikasi sukses
                if ($fbChannel && $fbChannel->wasChanged('status') && $fbChannel->status === 'connected') {
                    Notification::make()
                        ->title('✅ Facebook Berhasil Terhubung!')
                        ->body("Halaman <b>{$fbChannel->name}</b> siap digunakan untuk AI Auto-Reply.")
                        ->success()
                        ->send();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('AutoSync Error on ListChannels: ' . $e->getMessage());
            }
        }
    }
    
}
