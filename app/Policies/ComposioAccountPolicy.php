<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ComposioAccount;
use App\Models\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComposioAccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ComposioAccount');
    }

    public function view(AuthUser $authUser, ComposioAccount $composioAccount): bool
    {
        return $authUser->can('View:ComposioAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ComposioAccount');
    }

    public function update(AuthUser $authUser, ComposioAccount $composioAccount): bool
    {
        return $authUser->can('Update:ComposioAccount');
    }

    public function delete(AuthUser $authUser, ComposioAccount $composioAccount): bool
    {
        return $authUser->can('Delete:ComposioAccount');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ComposioAccount');
    }

    public function restore(AuthUser $authUser, ComposioAccount $composioAccount): bool
    {
        return $authUser->can('Restore:ComposioAccount');
    }

    public function forceDelete(AuthUser $authUser, ComposioAccount $composioAccount): bool
    {
        return $authUser->can('ForceDelete:ComposioAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ComposioAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ComposioAccount');
    }

    public function replicate(AuthUser $authUser, ComposioAccount $composioAccount): bool
    {
        return $authUser->can('Replicate:ComposioAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ComposioAccount');
    }
}
