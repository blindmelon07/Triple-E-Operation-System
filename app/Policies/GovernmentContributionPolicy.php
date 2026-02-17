<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GovernmentContribution;
use Illuminate\Auth\Access\HandlesAuthorization;

class GovernmentContributionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GovernmentContribution');
    }

    public function view(AuthUser $authUser, GovernmentContribution $governmentContribution): bool
    {
        return $authUser->can('View:GovernmentContribution');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GovernmentContribution');
    }

    public function update(AuthUser $authUser, GovernmentContribution $governmentContribution): bool
    {
        return $authUser->can('Update:GovernmentContribution');
    }

    public function delete(AuthUser $authUser, GovernmentContribution $governmentContribution): bool
    {
        return $authUser->can('Delete:GovernmentContribution');
    }

    public function restore(AuthUser $authUser, GovernmentContribution $governmentContribution): bool
    {
        return $authUser->can('Restore:GovernmentContribution');
    }

    public function forceDelete(AuthUser $authUser, GovernmentContribution $governmentContribution): bool
    {
        return $authUser->can('ForceDelete:GovernmentContribution');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GovernmentContribution');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GovernmentContribution');
    }

    public function replicate(AuthUser $authUser, GovernmentContribution $governmentContribution): bool
    {
        return $authUser->can('Replicate:GovernmentContribution');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GovernmentContribution');
    }

}