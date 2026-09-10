<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record;
        $firstOffice = $user->offices->first();
        if ($firstOffice) {
            setPermissionsTeamId($firstOffice->id);
        }
        $data['role_name'] = $user->roles()->first()?->name;
        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $roleName = $this->data['role_name'] ?? null;
        $offices = $this->data['offices'] ?? [];


        if ($roleName && !empty($offices)) {
            foreach ($offices as $officeId) {
                setPermissionsTeamId($officeId);
                $user->syncRoles([$roleName]);
            }

        }
    }
}
