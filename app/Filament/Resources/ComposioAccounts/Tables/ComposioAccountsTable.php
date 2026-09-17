<?php

namespace App\Filament\Resources\ComposioAccounts\Tables;

use App\Models\ComposioAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComposioAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->weight('bold'),

                // Menampilkan Badge Kuota: Misal "2 / 4 Kantor"
                TextColumn::make('offices_count')
                    ->counts('offices')
                    ->label('Slot Kantor Terpakai')
                    ->formatStateUsing(fn ($state, ComposioAccount $record) => "{$state} / {$record->max_offices} Kantor")
                    ->badge()
                    ->color(fn ($state, ComposioAccount $record) => $state >= $record->max_offices ? 'danger' : 'success'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

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
            ]);
    }
}
