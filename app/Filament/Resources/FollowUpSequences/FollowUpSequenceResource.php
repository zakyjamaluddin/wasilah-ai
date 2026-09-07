<?php

namespace App\Filament\Resources\FollowUpSequences;

use App\Filament\Resources\FollowUpSequences\Pages\CreateFollowUpSequence;
use App\Filament\Resources\FollowUpSequences\Pages\EditFollowUpSequence;
use App\Filament\Resources\FollowUpSequences\Pages\ListFollowUpSequences;
use App\Filament\Resources\FollowUpSequences\Schemas\FollowUpSequenceForm;
use App\Filament\Resources\FollowUpSequences\Tables\FollowUpSequencesTable;
use App\Models\FollowUpSequence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FollowUpSequenceResource extends Resource
{
    protected static ?string $model = FollowUpSequence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;
    protected static string | UnitEnum | null $navigationGroup = 'Automation & AI';


    public static function form(Schema $schema): Schema
    {
        return FollowUpSequenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FollowUpSequencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFollowUpSequences::route('/'),
            'create' => CreateFollowUpSequence::route('/create'),
            'edit' => EditFollowUpSequence::route('/{record}/edit'),
        ];
    }
}
