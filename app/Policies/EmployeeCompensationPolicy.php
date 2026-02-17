<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeCompensation;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeCompensationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeCompensation');
    }

    public function view(AuthUser $authUser, EmployeeCompensation $employeeCompensation): bool
    {
        return $authUser->can('View:EmployeeCompensation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeCompensation');
    }

    public function update(AuthUser $authUser, EmployeeCompensation $employeeCompensation): bool
    {
        return $authUser->can('Update:EmployeeCompensation');
    }

    public function delete(AuthUser $authUser, EmployeeCompensation $employeeCompensation): bool
    {
        return $authUser->can('Delete:EmployeeCompensation');
    }

    public function restore(AuthUser $authUser, EmployeeCompensation $employeeCompensation): bool
    {
        return $authUser->can('Restore:EmployeeCompensation');
    }

    public function forceDelete(AuthUser $authUser, EmployeeCompensation $employeeCompensation): bool
    {
        return $authUser->can('ForceDelete:EmployeeCompensation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeCompensation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeCompensation');
    }

    public function replicate(AuthUser $authUser, EmployeeCompensation $employeeCompensation): bool
    {
        return $authUser->can('Replicate:EmployeeCompensation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeCompensation');
    }

}