<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ContactGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContactGroup');
    }

    public function view(AuthUser $authUser, ContactGroup $contactGroup): bool
    {
        return $authUser->can('View:ContactGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContactGroup');
    }

    public function update(AuthUser $authUser, ContactGroup $contactGroup): bool
    {
        return $authUser->can('Update:ContactGroup');
    }

    public function delete(AuthUser $authUser, ContactGroup $contactGroup): bool
    {
        return $authUser->can('Delete:ContactGroup');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ContactGroup');
    }

    public function restore(AuthUser $authUser, ContactGroup $contactGroup): bool
    {
        return $authUser->can('Restore:ContactGroup');
    }

    public function forceDelete(AuthUser $authUser, ContactGroup $contactGroup): bool
    {
        return $authUser->can('ForceDelete:ContactGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContactGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContactGroup');
    }

    public function replicate(AuthUser $authUser, ContactGroup $contactGroup): bool
    {
        return $authUser->can('Replicate:ContactGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContactGroup');
    }

}