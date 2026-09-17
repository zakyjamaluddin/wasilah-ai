<?php

namespace App\Filament\Resources\ComposioAccounts\Pages;

use App\Filament\Resources\ComposioAccounts\ComposioAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComposioAccount extends EditRecord
{
    protected static string $resource = ComposioAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
