<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;


    protected function afterCreate(): void
    {
        $user = $this->record;
        $roleName = $this->data['roles'] ?? null;
        $offices = $this->data['offices'] ?? [];

        // 🔥 TEMPELKAN ROLE DENGAN OFFICE_ID YANG TEPAT UNTUK SETIAP KANTOR
        if ($roleName && !empty($offices)) {
            foreach ($offices as $officeId) {
                setPermissionsTeamId($officeId);
                $user->assignRole($roleName);
            }
        }
    }
}
