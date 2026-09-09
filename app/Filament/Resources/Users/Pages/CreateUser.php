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
        $roleName = $this->data['role_name'] ?? null;
        $offices = $this->data['offices'] ?? [];

        // 🔥 TEMPELKAN ROLE KE SETIAP KANTOR YANG DIPILIH
        if ($roleName && !empty($offices)) {
            foreach ($offices as $officeId) {
                setPermissionsTeamId($officeId);
                $user->assignRole($roleName);
            }
        }
    }
}
