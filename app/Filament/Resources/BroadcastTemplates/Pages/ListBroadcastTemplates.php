<?php

namespace App\Filament\Resources\BroadcastTemplates\Pages;

use App\Filament\Resources\BroadcastTemplates\BroadcastTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBroadcastTemplates extends ListRecords
{
    protected static string $resource = BroadcastTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
