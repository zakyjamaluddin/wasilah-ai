<?php

namespace App\Filament\Resources\Channels\Tables;

use App\Models\Channel;
use App\Services\ComposioService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChannelsTable
{
    public static function configure(Table $table): Table
    {
        $currentOffice = Filament::getTenant();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Channel')
                    ->searchable()
                    ->weight('bold'),

                BadgeColumn::make('type')
                    ->label('Platform')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'whatsapp'  => '💬 WhatsApp',
                        'facebook'  => '📘 Facebook',
                        'instagram' => '📷 Instagram',
                        default     => ucfirst($state),
                    })
                    ->colors([
                        'success' => 'whatsapp',
                        'info'    => 'facebook',
                        'warning' => 'instagram',
                    ]),

                TextColumn::make('identifier')
                    ->label('ID / Session')
                    ->placeholder('-'),

                BadgeColumn::make('status')
                    ->label('Status Koneksi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'connected'    => '🟢 Terhubung',
                        'disconnected' => '🔴 Terputus',
                        'scanning'     => '🟡 Menunggu Scan',
                        default        => ucfirst($state),
                    })
                    ->colors([
                        'success' => 'connected',
                        'danger'  => 'disconnected',
                        'warning' => 'scanning',
                    ]),

                IconColumn::make('is_bot_enabled')
                    ->label('AI Bot')
                    ->boolean(),
            ])
            ->recordActions([
                // 🟢 1. WHATSAPP: MODAL SCAN QR REALTIME
                Action::make('scanQr')
                    ->label('Scan QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn (Channel $record) => $record->type === 'whatsapp')
                    ->modalHeading(fn (Channel $record) => 'Scan QR WhatsApp - ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Channel $record) => view('filament.qr-modal-wrapper', ['channelId' => $record->id])),

                // 🔵 2. FACEBOOK: ROW ACTIONS
                Action::make('connect_facebook_row')
                    ->label(fn (Channel $record) => $record->status === 'connected' ? 'Hubungkan Ulang' : 'Hubungkan Facebook')
                    ->icon('heroicon-m-link')
                    ->color('info')
                    ->visible(fn (Channel $record) => $record->type === 'facebook')
                    ->action(function (Channel $record, ComposioService $composio) use ($currentOffice) {
                        if (!$currentOffice->composio_account_id) {
                            Notification::make()
                                ->title('Akun Composio Belum Dipilih!')
                                ->body('Silakan pilih Akun Composio pada pengaturan Kantor ini terlebih dahulu.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $authUrl = $composio->initiateOAuth($currentOffice, 'facebook');

                        if ($authUrl) {
                            return redirect()->away($authUrl);
                        }

                        Notification::make()
                            ->title('Gagal Memulai Sesi Composio')
                            ->danger()
                            ->send();
                    }),

                // 🔄 2B. AUTO-SYNC / CEK FANS PAGE FACEBOOK
                Action::make('sync_facebook_page')
                    ->label('Sinkronkan Halaman')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Channel $record) => $record->type === 'facebook')
                    ->action(function (Channel $record, ComposioService $composio) use ($currentOffice) {
                        $result = $composio->syncFacebookPages($currentOffice, $record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('✅ Fanspage Berhasil Disinkronkan!')
                                ->body("Halaman: <b>{$result['page_name']}</b> (ID: {$result['page_id']}) berhasil terhubung otomatis.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Sinkronisasi Halaman')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                        }
                    }),

                // 📷 3. INSTAGRAM: ROW ACTION HUBUNGKAN VIA COMPOSIO
                Action::make('connect_instagram_row')
                    ->label(fn (Channel $record) => $record->status === 'connected' ? 'Hubungkan Ulang' : 'Hubungkan Instagram')
                    ->icon('heroicon-m-camera')
                    ->color('warning')
                    ->visible(fn (Channel $record) => $record->type === 'instagram')
                    ->action(function (Channel $record, ComposioService $composio) use ($currentOffice) {
                        if (!$currentOffice->composio_account_id) {
                            Notification::make()
                                ->title('Akun Composio Belum Dipilih!')
                                ->body('Silakan pilih Akun Composio pada pengaturan Kantor ini terlebih dahulu.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $authUrl = $composio->initiateOAuth($currentOffice, 'instagram');

                        if ($authUrl) {
                            $record->update(['status' => 'connected']);
                            return redirect()->away($authUrl);
                        }

                        Notification::make()
                            ->title('Gagal Memulai Sesi Composio')
                            ->body('Periksa kembali API Key dan konfigurasi Composio Anda.')
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}