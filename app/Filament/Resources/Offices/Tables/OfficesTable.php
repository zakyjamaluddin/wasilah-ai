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
                    ->weight('bold')
                    ->icon('heroicon-o-building-office'),

                TextColumn::make('slug')
                    ->label('Slug URL')
                    ->badge()
                    ->color('gray'),

                // Metrik Jumlah Pengguna di Cabang Ini
                TextColumn::make('users_count')
                    ->label('Total Staff')
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                // Metrik Jumlah Channel Terhubung
                TextColumn::make('channels_count')
                    ->label('Channel Aktif')
                    ->counts('channels')
                    ->badge()
                    ->color('success'),

                // Metrik Total Leads
                TextColumn::make('contacts_count')
                    ->label('Total Leads')
                    ->counts('contacts')
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

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
