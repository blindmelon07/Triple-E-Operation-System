<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashRegisterSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashRegisterSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashRegisterSession');
    }

    public function view(AuthUser $authUser, CashRegisterSession $cashRegisterSession): bool
    {
        return $authUser->can('View:CashRegisterSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashRegisterSession');
    }

    public function update(AuthUser $authUser, CashRegisterSession $cashRegisterSession): bool
    {
        return $authUser->can('Update:CashRegisterSession');
    }

    public function delete(AuthUser $authUser, CashRegisterSession $cashRegisterSession): bool
    {
        return $authUser->can('Delete:CashRegisterSession');
    }

    public function restore(AuthUser $authUser, CashRegisterSession $cashRegisterSession): bool
    {
        return $authUser->can('Restore:CashRegisterSession');
    }

    public function forceDelete(AuthUser $authUser, CashRegisterSession $cashRegisterSession): bool
    {
        return $authUser->can('ForceDelete:CashRegisterSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashRegisterSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashRegisterSession');
    }

    public function replicate(AuthUser $authUser, CashRegisterSession $cashRegisterSession): bool
    {
        return $authUser->can('Replicate:CashRegisterSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashRegisterSession');
    }
}
