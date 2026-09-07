<?php

namespace App\Filament\Resources\BroadcastTemplates;

use App\Filament\Resources\BroadcastTemplates\Pages\CreateBroadcastTemplate;
use App\Filament\Resources\BroadcastTemplates\Pages\EditBroadcastTemplate;
use App\Filament\Resources\BroadcastTemplates\Pages\ListBroadcastTemplates;
use App\Filament\Resources\BroadcastTemplates\Schemas\BroadcastTemplateForm;
use App\Filament\Resources\BroadcastTemplates\Tables\BroadcastTemplatesTable;
use App\Models\BroadcastTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class BroadcastTemplateResource extends Resource
{
    protected static ?string $model = BroadcastTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;
    protected static string | UnitEnum | null $navigationGroup = 'Broadcasts';

    public static function form(Schema $schema): Schema
    {
        return BroadcastTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BroadcastTemplatesTable::configure($table);
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
            'index' => ListBroadcastTemplates::route('/'),
            'create' => CreateBroadcastTemplate::route('/create'),
            'edit' => EditBroadcastTemplate::route('/{record}/edit'),
        ];
    }
}
