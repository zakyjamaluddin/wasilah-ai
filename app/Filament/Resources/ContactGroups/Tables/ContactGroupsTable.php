<?php

namespace App\Filament\Resources\ContactGroups\Tables;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\BaileysService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Grup')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('contacts_count')
                    ->label('Total Anggota')
                    ->counts('contacts')
                    ->badge()
                    ->color('success'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                // 🔥 1. ACTION IMPORT DARI GRUP WHATSAPP TERHUBUNG
                Action::make('importFromWaGroup')
                    ->label('Import dari Grup WA')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Select::make('channel_id')
                            ->label('Pilih Akun WhatsApp Kantor')
                            ->options(fn () => Channel::where('type', 'whatsapp')->where('status', 'connected')->pluck('name', 'id'))
                            ->required()
                            ->live(),
                        Select::make('group_id')
                            ->label('Pilih Grup WhatsApp')
                            ->options(function (Get $get, BaileysService $baileys) {
                                $channelId = $get('channel_id');
                                if (!$channelId) return [];

                                $channel = Channel::find($channelId);
                                if (!$channel) return [];

                                $response = $baileys->getGroups($channel->identifier);
                                $groups = $response['data'] ?? [];

                                return collect($groups)->pluck('subject', 'groupId')->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->helperText('Pilih grup WA untuk menarik semua nomor anggotanya.'),
                    ])
                    ->action(function (ContactGroup $record, array $data, BaileysService $baileys) {
                        $channel = Channel::find($data['channel_id']);
                        if (!$channel) return;

                        $response = $baileys->getGroupMembers($channel->identifier, $data['group_id']);
                        $members = $response['data']['members'] ?? [];

                        if (empty($members)) {
                            Notification::make()->title('Gagal menarik anggota atau grup kosong')->danger()->send();
                            return;
                        }

                        $importedCount = 0;
                        $officeId = $record->office_id;

                        foreach ($members as $member) {
                            $phoneOrLid = $member['phoneNumber'];
                            $name = $member['name'] ?? ('Member WA ' . substr($phoneOrLid, -4));

                            // Cari atau buat kontak di level kantor
                            $contact = Contact::firstOrCreate(
                                [
                                    'office_id' => $officeId,
                                    'wa_jid' => $phoneOrLid,
                                ],
                                [
                                    'name' => $name,
                                    'phone_number' => str_contains($phoneOrLid, '@lid') ? null : $phoneOrLid,
                                    'pipeline_stage' => 'lead',
                                ]
                            );

                            // Hubungkan ke grup kontak (syncWithoutDetaching otomatis mencegah duplikasi!)
                            $record->contacts()->syncWithoutDetaching([$contact->id]);
                            $importedCount++;
                        }

                        Notification::make()
                            ->title("Berhasil mengimpor {$importedCount} kontak dari grup WA!")
                            ->success()
                            ->send();
                    }),

                // 🔥 2. ACTION IMPORT DARI BUKU TELEPON WHATSAPP
                Action::make('importFromPhonebook')
                    ->label('Import Kontak HP')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('info')
                    ->form([
                        Select::make('channel_id')
                            ->label('Pilih Akun WhatsApp Kantor')
                            ->options(fn () => Channel::where('type', 'whatsapp')->where('status', 'connected')->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (ContactGroup $record, array $data, BaileysService $baileys) {
                        $channel = Channel::find($data['channel_id']);
                        if (!$channel) return;

                        $response = $baileys->getPhonebook($channel->identifier);
                        $contacts = $response['data'] ?? [];

                        if (empty($contacts)) {
                            Notification::make()->title('Tidak ada kontak buku telepon yang ditemukan')->warning()->send();
                            return;
                        }

                        $importedCount = 0;
                        $officeId = $record->office_id;

                        foreach ($contacts as $c) {
                            $phone = $c['phoneNumber'] ?? '';
                            $name = $c['name'] ?? 'Kontak WA';
                            if (empty($phone)) continue;

                            $contact = Contact::firstOrCreate(
                                [
                                    'office_id' => $officeId,
                                    'wa_jid' => $phone,
                                ],
                                [
                                    'name' => $name,
                                    'phone_number' => $phone,
                                    'pipeline_stage' => 'lead',
                                ]
                            );

                            $record->contacts()->syncWithoutDetaching([$contact->id]);
                            $importedCount++;
                        }

                        Notification::make()
                            ->title("Berhasil mengimpor {$importedCount} kontak dari buku telepon!")
                            ->success()
                            ->send();
                    }),

                // 🔥 3. ACTION IMPORT DARI FILE EXCEL / CSV
                // 🔥 3. ACTION IMPORT DARI FILE EXCEL / CSV (SMART DELIMITER & ACCURATE PATH)
                Action::make('importFromCsv')
                    ->label('Import Excel/CSV')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('warning')
                    ->form([
                        FileUpload::make('file')
                            ->label('Pilih File CSV / Excel')
                            ->disk('public')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/comma-separated-values'
                            ])
                            ->required()
                            ->helperText('Format kolom wajib: Kolom 1 = Nama, Kolom 2 = Nomor HP (08xx atau 628xx).'),
                    ])
                    ->action(function (ContactGroup $record, array $data) {
                        $disk = \Illuminate\Support\Facades\Storage::disk('public');
                        
                        if (!$disk->exists($data['file'])) {
                            Notification::make()->title('File gagal diunggah ke storage')->danger()->send();
                            return;
                        }

                        $fullPath = $disk->path($data['file']);
                        $officeId = $record->office_id;
                        $importedCount = 0;

                        if (($handle = fopen($fullPath, 'r')) !== false) {
                            // 🔍 Deteksi otomatis apakah pemisah kolom menggunakan KOMA (,) atau TITIK KOMA (;)
                            $firstLine = fgets($handle);
                            $delimiter = str_contains($firstLine, ';') ? ';' : ',';
                            rewind($handle); // Kembalikan pointer ke awal file

                            $rowNumber = 0;
                            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                                $rowNumber++;
                                
                                // Lewati baris header jika baris pertama berisi kata "nama" / "name" / "no"
                                $col1 = strtolower(trim($row[0] ?? ''));
                                if ($rowNumber === 1 && in_array($col1, ['nama', 'name', 'no', 'nama lengkap', 'customer'])) {
                                    continue;
                                }

                                $name = trim($row[0] ?? '');
                                $rawPhone = trim($row[1] ?? '');

                                if (empty($rawPhone)) continue;

                                // Format nomor HP agar standar (628xxx)
                                $cleanPhone = preg_replace('/\D/', '', $rawPhone);
                                if (str_starts_with($cleanPhone, '0')) {
                                    $cleanPhone = '62' . substr($cleanPhone, 1);
                                }

                                if (empty($name)) {
                                    $name = 'Kontak ' . substr($cleanPhone, -4);
                                }

                                // 1. Cari atau buat kontak di level kantor
                                $contact = \App\Models\Contact::firstOrCreate(
                                    [
                                        'office_id' => $officeId,
                                        'wa_jid' => $cleanPhone . '@s.whatsapp.net',
                                    ],
                                    [
                                        'name' => $name,
                                        'phone_number' => $cleanPhone,
                                        'pipeline_stage' => 'lead',
                                    ]
                                );

                                // 2. Hubungkan ke grup kontak (Anti-Duplikasi)
                                $record->contacts()->syncWithoutDetaching([$contact->id]);
                                $importedCount++;
                            }
                            fclose($handle);

                            // Bersihkan file sementara setelah selesai di-import
                            $disk->delete($data['file']);
                        }

                        Notification::make()
                            ->title("Berhasil mengimpor {$importedCount} kontak ke grup {$record->name}!")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
