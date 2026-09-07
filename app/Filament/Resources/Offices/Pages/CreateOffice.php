<?php

namespace App\Filament\Resources\Offices\Pages;

use App\Filament\Resources\Offices\OfficeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOffice extends CreateRecord
{
    protected static string $resource = OfficeResource::class;

    protected function afterCreate(): void
    {
        $office = $this->record;
        $user = Auth::user();

        // Otomatis hubungkan pembuat kantor ke kantor baru ini
        if ($user && !$user->offices()->where('offices.id', $office->id)->exists()) {
            $user->offices()->attach($office->id);
        }
    }
}
