<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FollowUpSequence;
use Illuminate\Auth\Access\HandlesAuthorization;

class FollowUpSequencePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FollowUpSequence');
    }

    public function view(AuthUser $authUser, FollowUpSequence $followUpSequence): bool
    {
        return $authUser->can('View:FollowUpSequence');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FollowUpSequence');
    }

    public function update(AuthUser $authUser, FollowUpSequence $followUpSequence): bool
    {
        return $authUser->can('Update:FollowUpSequence');
    }

    public function delete(AuthUser $authUser, FollowUpSequence $followUpSequence): bool
    {
        return $authUser->can('Delete:FollowUpSequence');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FollowUpSequence');
    }

    public function restore(AuthUser $authUser, FollowUpSequence $followUpSequence): bool
    {
        return $authUser->can('Restore:FollowUpSequence');
    }

    public function forceDelete(AuthUser $authUser, FollowUpSequence $followUpSequence): bool
    {
        return $authUser->can('ForceDelete:FollowUpSequence');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FollowUpSequence');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FollowUpSequence');
    }

    public function replicate(AuthUser $authUser, FollowUpSequence $followUpSequence): bool
    {
        return $authUser->can('Replicate:FollowUpSequence');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FollowUpSequence');
    }

}