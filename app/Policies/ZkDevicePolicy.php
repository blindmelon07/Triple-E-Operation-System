<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ZkDevice;
use Illuminate\Auth\Access\HandlesAuthorization;

class ZkDevicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ZkDevice');
    }

    public function view(AuthUser $authUser, ZkDevice $zkDevice): bool
    {
        return $authUser->can('View:ZkDevice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ZkDevice');
    }

    public function update(AuthUser $authUser, ZkDevice $zkDevice): bool
    {
        return $authUser->can('Update:ZkDevice');
    }

    public function delete(AuthUser $authUser, ZkDevice $zkDevice): bool
    {
        return $authUser->can('Delete:ZkDevice');
    }

    public function restore(AuthUser $authUser, ZkDevice $zkDevice): bool
    {
        return $authUser->can('Restore:ZkDevice');
    }

    public function forceDelete(AuthUser $authUser, ZkDevice $zkDevice): bool
    {
        return $authUser->can('ForceDelete:ZkDevice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ZkDevice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ZkDevice');
    }

    public function replicate(AuthUser $authUser, ZkDevice $zkDevice): bool
    {
        return $authUser->can('Replicate:ZkDevice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ZkDevice');
    }

}