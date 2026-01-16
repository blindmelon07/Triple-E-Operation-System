<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MaintenanceRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenanceRecord');
    }

    public function view(AuthUser $authUser, MaintenanceRecord $maintenanceRecord): bool
    {
        return $authUser->can('View:MaintenanceRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenanceRecord');
    }

    public function update(AuthUser $authUser, MaintenanceRecord $maintenanceRecord): bool
    {
        return $authUser->can('Update:MaintenanceRecord');
    }

    public function delete(AuthUser $authUser, MaintenanceRecord $maintenanceRecord): bool
    {
        return $authUser->can('Delete:MaintenanceRecord');
    }

    public function restore(AuthUser $authUser, MaintenanceRecord $maintenanceRecord): bool
    {
        return $authUser->can('Restore:MaintenanceRecord');
    }

    public function forceDelete(AuthUser $authUser, MaintenanceRecord $maintenanceRecord): bool
    {
        return $authUser->can('ForceDelete:MaintenanceRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenanceRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenanceRecord');
    }

    public function replicate(AuthUser $authUser, MaintenanceRecord $maintenanceRecord): bool
    {
        return $authUser->can('Replicate:MaintenanceRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaintenanceRecord');
    }

}