<?php

namespace App\Filament\Resources\FollowUpSequences\Pages;

use App\Filament\Resources\FollowUpSequences\FollowUpSequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFollowUpSequence extends EditRecord
{
    protected static string $resource = FollowUpSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
