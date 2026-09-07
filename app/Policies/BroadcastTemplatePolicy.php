<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BroadcastTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class BroadcastTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BroadcastTemplate');
    }

    public function view(AuthUser $authUser, BroadcastTemplate $broadcastTemplate): bool
    {
        return $authUser->can('View:BroadcastTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BroadcastTemplate');
    }

    public function update(AuthUser $authUser, BroadcastTemplate $broadcastTemplate): bool
    {
        return $authUser->can('Update:BroadcastTemplate');
    }

    public function delete(AuthUser $authUser, BroadcastTemplate $broadcastTemplate): bool
    {
        return $authUser->can('Delete:BroadcastTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BroadcastTemplate');
    }

    public function restore(AuthUser $authUser, BroadcastTemplate $broadcastTemplate): bool
    {
        return $authUser->can('Restore:BroadcastTemplate');
    }

    public function forceDelete(AuthUser $authUser, BroadcastTemplate $broadcastTemplate): bool
    {
        return $authUser->can('ForceDelete:BroadcastTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BroadcastTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BroadcastTemplate');
    }

    public function replicate(AuthUser $authUser, BroadcastTemplate $broadcastTemplate): bool
    {
        return $authUser->can('Replicate:BroadcastTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BroadcastTemplate');
    }

}