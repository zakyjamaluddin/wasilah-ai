<?php

namespace App\Filament\Resources\Offices\Tables;

use App\Models\Office;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OfficesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kantor / Cabang')
                    ->searchable()
                    ->weight('bold'),

                // Tambahkan di dalam table() OfficeResource:
                TextColumn::make('effective_subscription_status')
                    ->label('Status Langganan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'expiring' => 'warning',
                        'inactive' => 'danger',
                        'free'     => 'gray',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'   => '🟢 Aktif',
                        'expiring' => '🟡 Hampir Habis',
                        'inactive' => '🔴 Kedaluwarsa',
                        'free'     => '⚪ Free',
                        default    => ucfirst($state),
                    }),

                TextColumn::make('expired_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y')
                    ->placeholder('Belum diatur')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // 🔥 AKSI TAUTAN UNDANGAN PENGELOLA KANTOR
                Action::make('inviteLink')
                    ->label('Tautan Undangan')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading(fn (Office $record) => 'Undang Pengelola: ' . $record->name)
                    ->modalDescription('Bagikan tautan ini kepada calon pengelola atau staf. Pengguna cukup membuka tautan dan signup dengan Google untuk otomatis mendapatkan akses pengelola (Admin) di kantor ini.')
                    ->modalIcon('heroicon-o-link')
                    ->fillForm(fn (Office $record): array => [
                        'invitation_url' => $record->getInvitationUrl(),
                    ])
                    ->form([
                        TextInput::make('invitation_url')
                            ->label('Tautan Undangan Khusus')
                            ->readOnly()
                            ->copyable(copyMessage: 'Tautan undangan berhasil disalin!')
                            ->helperText('Klik ikon salin di sisi kanan input untuk menyalin tautan.'),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraModalFooterActions([
                        Action::make('regenerate')
                            ->label('Buat Ulang Tautan (Reset)')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Reset Tautan Undangan?')
                            ->modalDescription('Tautan lama akan kedaluwarsa dan tidak dapat digunakan lagi. Calon pengelola harus menggunakan tautan baru.')
                            ->action(function (Office $record) {
                                $record->regenerateInvitationCode();
                                Notification::make()
                                    ->title('Tautan undangan baru berhasil dibuat!')
                                    ->success()
                                    ->send();
                            })
                    ]),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
