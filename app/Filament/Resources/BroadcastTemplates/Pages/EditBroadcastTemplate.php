<?php

namespace App\Filament\Resources\BroadcastTemplates\Pages;

use App\Filament\Resources\BroadcastTemplates\BroadcastTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBroadcastTemplate extends EditRecord
{
    protected static string $resource = BroadcastTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
