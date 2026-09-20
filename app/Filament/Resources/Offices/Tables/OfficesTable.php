<?php

namespace App\Filament\Resources\Offices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
