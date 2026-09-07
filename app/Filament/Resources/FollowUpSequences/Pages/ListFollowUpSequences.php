<?php

namespace App\Filament\Resources\FollowUpSequences\Pages;

use App\Filament\Resources\FollowUpSequences\FollowUpSequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFollowUpSequences extends ListRecords
{
    protected static string $resource = FollowUpSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
