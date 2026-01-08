<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MaintenanceType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MaintenanceTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenanceType');
    }

    public function view(AuthUser $authUser, MaintenanceType $maintenanceType): bool
    {
        return $authUser->can('View:MaintenanceType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenanceType');
    }

    public function update(AuthUser $authUser, MaintenanceType $maintenanceType): bool
    {
        return $authUser->can('Update:MaintenanceType');
    }

    public function delete(AuthUser $authUser, MaintenanceType $maintenanceType): bool
    {
        return $authUser->can('Delete:MaintenanceType');
    }

    public function restore(AuthUser $authUser, MaintenanceType $maintenanceType): bool
    {
        return $authUser->can('Restore:MaintenanceType');
    }

    public function forceDelete(AuthUser $authUser, MaintenanceType $maintenanceType): bool
    {
        return $authUser->can('ForceDelete:MaintenanceType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenanceType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenanceType');
    }

    public function replicate(AuthUser $authUser, MaintenanceType $maintenanceType): bool
    {
        return $authUser->can('Replicate:MaintenanceType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaintenanceType');
    }
}
