<?php

namespace App\Filament\Resources\Channels\Tables;

use App\Models\Channel;
use App\Services\BaileysService;
use App\Services\MetaGraphService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;


class ChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Channel')
                    ->searchable()
                    ->weight('bold'),
                BadgeColumn::make('type')
                    ->label('Platform')
                    ->colors([
                        'success' => 'whatsapp',
                        'info' => 'facebook',
                        'warning' => 'instagram',
                    ]),
                TextColumn::make('identifier')
                    ->label('Session ID'),
                BadgeColumn::make('status')
                    ->label('Status Koneksi')
                    ->colors([
                        'success' => 'connected',
                        'danger' => 'disconnected',
                        'warning' => 'scanning',
                    ]),
                IconColumn::make('is_bot_enabled')
                    ->label('AI Bot')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->actions([
                // 🔥 ACTION SCAN QR CODE LANGSUNG DARI FILAMENT
                // 🔥 ACTION SCAN QR REALTIME (LIVEWIRE)
                Action::make('scanQr')
                    ->label('Scan QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn (Channel $record) => $record->type === 'whatsapp')
                    ->modalHeading(fn (Channel $record) => 'Scan QR - ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Channel $record) => view('filament.qr-modal-wrapper', ['channelId' => $record->id])),

                Action::make('syncMeta')
                    ->label('Cek Koneksi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Channel $record) => in_array($record->type, ['facebook', 'instagram']))
                    ->action(function (Channel $record, MetaGraphService $meta) {
                        $token = $record->credentials['access_token'] ?? null;
                        if (!$token) {
                            Notification::make()->title('Access Token belum diisi!')->danger()->send();
                            return;
                        }

                        $res = $meta->subscribePageWebhook($record->identifier, $token);

                        if (!empty($res['success']) && $res['success'] === true) {
                            $record->update(['status' => 'connected']);
                            Notification::make()
                                ->title("✅ Halaman {$record->name} Berhasil Terhubung & Webhook Aktif!")
                                ->success()
                                ->send();
                        } else {
                            $record->update(['status' => 'disconnected']);
                            $errMsg = $res['error']['message'] ?? 'Gagal menghubungkan ke Facebook Meta';
                            Notification::make()
                                ->title('Gagal Terhubung ke Meta: ' . $errMsg)
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
