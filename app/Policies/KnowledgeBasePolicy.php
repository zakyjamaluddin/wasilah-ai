<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KnowledgeBase;
use Illuminate\Auth\Access\HandlesAuthorization;

class KnowledgeBasePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KnowledgeBase');
    }

    public function view(AuthUser $authUser, KnowledgeBase $knowledgeBase): bool
    {
        return $authUser->can('View:KnowledgeBase');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KnowledgeBase');
    }

    public function update(AuthUser $authUser, KnowledgeBase $knowledgeBase): bool
    {
        return $authUser->can('Update:KnowledgeBase');
    }

    public function delete(AuthUser $authUser, KnowledgeBase $knowledgeBase): bool
    {
        return $authUser->can('Delete:KnowledgeBase');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KnowledgeBase');
    }

    public function restore(AuthUser $authUser, KnowledgeBase $knowledgeBase): bool
    {
        return $authUser->can('Restore:KnowledgeBase');
    }

    public function forceDelete(AuthUser $authUser, KnowledgeBase $knowledgeBase): bool
    {
        return $authUser->can('ForceDelete:KnowledgeBase');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KnowledgeBase');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KnowledgeBase');
    }

    public function replicate(AuthUser $authUser, KnowledgeBase $knowledgeBase): bool
    {
        return $authUser->can('Replicate:KnowledgeBase');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KnowledgeBase');
    }

}