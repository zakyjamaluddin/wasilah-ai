<?php

namespace App\Filament\Resources\BroadcastCampaigns;

use App\Filament\Resources\BroadcastCampaigns\Pages\CreateBroadcastCampaign;
use App\Filament\Resources\BroadcastCampaigns\Pages\EditBroadcastCampaign;
use App\Filament\Resources\BroadcastCampaigns\Pages\ListBroadcastCampaigns;
use App\Filament\Resources\BroadcastCampaigns\Schemas\BroadcastCampaignForm;
use App\Filament\Resources\BroadcastCampaigns\Tables\BroadcastCampaignsTable;
use App\Models\BroadcastCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BroadcastCampaignResource extends Resource
{
    protected static ?string $model = BroadcastCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;
    protected static string | UnitEnum | null $navigationGroup = 'Broadcasts';


    public static function form(Schema $schema): Schema
    {
        return BroadcastCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BroadcastCampaignsTable::configure($table);
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
            'index' => ListBroadcastCampaigns::route('/'),
            'create' => CreateBroadcastCampaign::route('/create'),
            'edit' => EditBroadcastCampaign::route('/{record}/edit'),
        ];
    }
}
