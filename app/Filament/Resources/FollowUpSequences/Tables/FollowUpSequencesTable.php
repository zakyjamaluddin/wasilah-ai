<?php

namespace App\Filament\Resources\FollowUpSequences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowUpSequencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Sequence')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('steps_count')
                    ->label('Total Langkah')
                    ->counts('steps')
                    ->badge()
                    ->color('info'),
                TextColumn::make('enrollments_count')
                    ->label('Leads Berjalan')
                    ->counts('enrollments')
                    ->badge()
                    ->color('success'),
                BadgeColumn::make('platform')
                    ->label('Platform'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
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
