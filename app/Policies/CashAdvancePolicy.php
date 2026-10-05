<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashAdvance;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashAdvancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashAdvance');
    }

    public function view(AuthUser $authUser, CashAdvance $cashAdvance): bool
    {
        return $authUser->can('View:CashAdvance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashAdvance');
    }

    public function update(AuthUser $authUser, CashAdvance $cashAdvance): bool
    {
        return $authUser->can('Update:CashAdvance');
    }

    public function delete(AuthUser $authUser, CashAdvance $cashAdvance): bool
    {
        return $authUser->can('Delete:CashAdvance');
    }

    public function restore(AuthUser $authUser, CashAdvance $cashAdvance): bool
    {
        return $authUser->can('Restore:CashAdvance');
    }

    public function forceDelete(AuthUser $authUser, CashAdvance $cashAdvance): bool
    {
        return $authUser->can('ForceDelete:CashAdvance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashAdvance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashAdvance');
    }

    public function replicate(AuthUser $authUser, CashAdvance $cashAdvance): bool
    {
        return $authUser->can('Replicate:CashAdvance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashAdvance');
    }

}
