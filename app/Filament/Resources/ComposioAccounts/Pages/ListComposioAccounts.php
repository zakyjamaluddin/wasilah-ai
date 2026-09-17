<?php

namespace App\Filament\Resources\ComposioAccounts\Pages;

use App\Filament\Resources\ComposioAccounts\ComposioAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComposioAccounts extends ListRecords
{
    protected static string $resource = ComposioAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
