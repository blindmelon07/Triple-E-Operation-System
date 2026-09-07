<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FuelLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class FuelLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FuelLog');
    }

    public function view(AuthUser $authUser, FuelLog $fuelLog): bool
    {
        return $authUser->can('View:FuelLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FuelLog');
    }

    public function update(AuthUser $authUser, FuelLog $fuelLog): bool
    {
        return $authUser->can('Update:FuelLog');
    }

    public function delete(AuthUser $authUser, FuelLog $fuelLog): bool
    {
        return $authUser->can('Delete:FuelLog');
    }

    public function restore(AuthUser $authUser, FuelLog $fuelLog): bool
    {
        return $authUser->can('Restore:FuelLog');
    }

    public function forceDelete(AuthUser $authUser, FuelLog $fuelLog): bool
    {
        return $authUser->can('ForceDelete:FuelLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FuelLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FuelLog');
    }

    public function replicate(AuthUser $authUser, FuelLog $fuelLog): bool
    {
        return $authUser->can('Replicate:FuelLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FuelLog');
    }

}