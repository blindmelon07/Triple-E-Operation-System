<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incentive;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class IncentivePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Incentive');
    }

    public function view(AuthUser $authUser, Incentive $incentive): bool
    {
        return $authUser->can('View:Incentive');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Incentive');
    }

    public function update(AuthUser $authUser, Incentive $incentive): bool
    {
        return $authUser->can('Update:Incentive');
    }

    public function delete(AuthUser $authUser, Incentive $incentive): bool
    {
        return $authUser->can('Delete:Incentive');
    }

    public function restore(AuthUser $authUser, Incentive $incentive): bool
    {
        return $authUser->can('Restore:Incentive');
    }

    public function forceDelete(AuthUser $authUser, Incentive $incentive): bool
    {
        return $authUser->can('ForceDelete:Incentive');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Incentive');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Incentive');
    }

    public function replicate(AuthUser $authUser, Incentive $incentive): bool
    {
        return $authUser->can('Replicate:Incentive');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Incentive');
    }
}
