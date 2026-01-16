<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MaintenanceRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenanceRequest');
    }

    public function view(AuthUser $authUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $authUser->can('View:MaintenanceRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenanceRequest');
    }

    public function update(AuthUser $authUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $authUser->can('Update:MaintenanceRequest');
    }

    public function delete(AuthUser $authUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $authUser->can('Delete:MaintenanceRequest');
    }

    public function restore(AuthUser $authUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $authUser->can('Restore:MaintenanceRequest');
    }

    public function forceDelete(AuthUser $authUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $authUser->can('ForceDelete:MaintenanceRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenanceRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenanceRequest');
    }

    public function replicate(AuthUser $authUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $authUser->can('Replicate:MaintenanceRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaintenanceRequest');
    }

}