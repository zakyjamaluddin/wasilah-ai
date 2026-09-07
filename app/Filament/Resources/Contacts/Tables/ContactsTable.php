<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Leads')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('phone_number')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->default(fn (Contact $record) => $record->wa_jid ?: '-'),
                BadgeColumn::make('pipeline_stage')
                    ->label('Status Pipeline')
                    ->colors([
                        'gray' => 'lead',
                        'info' => 'cold_prospect',
                        'warning' => 'warm_prospect',
                        'danger' => 'hot_prospect',
                        'primary' => 'closing',
                        'success' => 'won',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lead' => 'Baru',
                        'cold_prospect' => 'Cold',
                        'warm_prospect' => 'Warm',
                        'hot_prospect' => 'Hot 🔥',
                        'closing' => 'Closing',
                        'won' => 'Won 🎉',
                        'lost' => 'Lost',
                        default => $state,
                    }),
                TagsColumn::make('groups.name')
                    ->label('Grup Kontak'),
                TextColumn::make('created_at')
                    ->label('Tanggal Masuk')
                    ->dateTime('d M Y')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('pipeline_stage')
                    ->label('Filter Prospek')
                    ->options([
                        'lead' => 'Baru',
                        'cold_prospect' => 'Cold Prospect',
                        'warm_prospect' => 'Warm Prospect',
                        'hot_prospect' => 'Hot Prospect',
                        'closing' => 'Closing',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ]),
                SelectFilter::make('groups')
                    ->label('Filter Grup')
                    ->relationship('groups', 'name'),
            ])
            ->recordActions([
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
