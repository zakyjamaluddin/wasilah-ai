<?php

namespace App\Filament\Resources\BroadcastCampaigns\Tables;

use App\Models\BroadcastCampaign;
use App\Services\BaileysService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class BroadcastCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Campaign')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('channel.name')
                    ->label('WhatsApp Pengirim')
                    ->badge()
                    ->color('success'),

                // 🔥 PROGRESS BAR REAL-TIME
                // 🔥 PROGRESS BAR REAL-TIME DENGAN AUTO-SYNC DARI SERVER
                TextColumn::make('progress')
                    ->label('Progress Pengiriman')
                    ->formatStateUsing(function (BroadcastCampaign $record, BaileysService $baileys) {
                        // Jika status masih processing, sinkronkan langsung dengan server Baileys
                        if ($record->status === 'processing' && $record->external_broadcast_id) {
                            try {
                                $statusRes = $baileys->client()->get("/api/broadcast/status/{$record->external_broadcast_id}")->json();
                                if (!empty($statusRes['data']['progress'])) {
                                    $p = $statusRes['data']['progress'];
                                    $isDone = ($p['sent'] + $p['failed']) >= $p['total'];
                                    $record->update([
                                        'sent_count' => $p['sent'],
                                        'failed_count' => $p['failed'],
                                        'status' => $isDone ? 'completed' : 'processing',
                                    ]);
                                }
                            } catch (\Exception $e) {}
                        }

                        $total = $record->total_recipients ?: 1;
                        $processed = $record->sent_count + $record->failed_count;
                        $percentage = min(100, round(($processed / $total) * 100));
                        $barColor = $record->status === 'completed' ? 'bg-emerald-500' : 'bg-indigo-600';

                        return new HtmlString("
                            <div class='w-full space-y-1'>
                                <div class='flex justify-between text-xs font-semibold'>
                                    <span>{$processed} / {$record->total_recipients} Penerima</span>
                                    <span>{$percentage}%</span>
                                </div>
                                <div class='w-full bg-neutral-200 rounded-full h-2 overflow-hidden'>
                                    <div class='{$barColor} h-2 rounded-full transition-all duration-500' style='width: {$percentage}%'></div>
                                </div>
                            </div>
                        ");
                    }),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('sent_count')
                    ->label('Berhasil')
                    ->badge()
                    ->color('success'),
                TextColumn::make('failed_count')
                    ->label('Gagal')
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('dispatch')
                    ->label('Luncurkan 🚀')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (BroadcastCampaign $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Peluncuran Broadcast')
                    ->modalDescription('Pesan akan dikirimkan dengan jeda waktu aman anti-ban (5-12 detik per pesan). Apakah Anda yakin?')
                    ->action(function (BroadcastCampaign $record, BaileysService $baileys) {
                        // 1. Ambil seluruh kontak dari grup sasaran
                        $channel = $record->channel;
                        if (!$channel) {
                            Notification::make()->title('Channel WhatsApp pengirim tidak ditemukan')->danger()->send();
                            return;
                        }


                        // 🎯 1. AMBIL HANYA KONTAK DARI GRUP SASARAN YANG DIPILIH
                        $group = \App\Models\ContactGroup::with('contacts')->find($record->contact_group_id);
                        if (!$group || $group->contacts->isEmpty()) {
                            Notification::make()->title('Grup kontak kosong! Tidak ada nomor sasaran.')->danger()->send();
                            return;
                        }

                        $recipients = [];
                        foreach ($group->contacts as $c) {
                            $target = $c->wa_jid ?: $c->phone_number;
                            if (!$target) continue;
                            $recipients[] = [
                                'target' => $target,
                                'name' => $c->name,
                            ];
                        }

                        if (empty($recipients)) {
                            Notification::make()->title('Tidak ada nomor valid di dalam grup ini')->danger()->send();
                            return;
                        }

                        $record->update([
                            'total_recipients' => count($recipients),
                            'status' => 'processing',
                            'started_at' => now(),
                        ]);

                        // 2. URL Gambar jika ada
                        // 🖼️ 2. UBAH GAMBAR LOKAL MENJADI BASE64 AGAR 100% TERKIRIM
                        $mediaBase64 = null;
                        if ($record->media_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->media_url)) {
                            $mediaBase64 = base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($record->media_url));
                        }
                        // $mediaUrl = $record->media_url ? url('storage/' . $record->media_url) : null;

                        // 3. Tembak ke VPS Baileys Microservice Queue Worker
                        $res = $baileys->dispatchBroadcast(
                            $channel->identifier,
                            $recipients,
                            $record->message,
                            // $mediaUrl
                            $mediaBase64
                        );

                        if (!empty($res['data']['broadcastId'])) {
                            $record->update(['external_broadcast_id' => $res['data']['broadcastId']]);
                            Notification::make()->title('Broadcast berhasil diluncurkan ke antrean anti-ban!')->success()->send();
                        } else {
                            $record->update(['status' => 'failed']);
                            Notification::make()->title('Gagal meluncurkan broadcast ke server')->danger()->send();
                        }
                    }),

                DeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
