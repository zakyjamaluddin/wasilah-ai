<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BroadcastCampaign;
use Illuminate\Auth\Access\HandlesAuthorization;

class BroadcastCampaignPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BroadcastCampaign');
    }

    public function view(AuthUser $authUser, BroadcastCampaign $broadcastCampaign): bool
    {
        return $authUser->can('View:BroadcastCampaign');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BroadcastCampaign');
    }

    public function update(AuthUser $authUser, BroadcastCampaign $broadcastCampaign): bool
    {
        return $authUser->can('Update:BroadcastCampaign');
    }

    public function delete(AuthUser $authUser, BroadcastCampaign $broadcastCampaign): bool
    {
        return $authUser->can('Delete:BroadcastCampaign');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BroadcastCampaign');
    }

    public function restore(AuthUser $authUser, BroadcastCampaign $broadcastCampaign): bool
    {
        return $authUser->can('Restore:BroadcastCampaign');
    }

    public function forceDelete(AuthUser $authUser, BroadcastCampaign $broadcastCampaign): bool
    {
        return $authUser->can('ForceDelete:BroadcastCampaign');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BroadcastCampaign');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BroadcastCampaign');
    }

    public function replicate(AuthUser $authUser, BroadcastCampaign $broadcastCampaign): bool
    {
        return $authUser->can('Replicate:BroadcastCampaign');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BroadcastCampaign');
    }

}