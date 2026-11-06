<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MiscellaneousExpense;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MiscellaneousExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MiscellaneousExpense');
    }

    public function view(AuthUser $authUser, MiscellaneousExpense $miscellaneousExpense): bool
    {
        return $authUser->can('View:MiscellaneousExpense');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MiscellaneousExpense');
    }

    public function update(AuthUser $authUser, MiscellaneousExpense $miscellaneousExpense): bool
    {
        return $authUser->can('Update:MiscellaneousExpense');
    }

    public function delete(AuthUser $authUser, MiscellaneousExpense $miscellaneousExpense): bool
    {
        return $authUser->can('Delete:MiscellaneousExpense');
    }

    public function restore(AuthUser $authUser, MiscellaneousExpense $miscellaneousExpense): bool
    {
        return $authUser->can('Restore:MiscellaneousExpense');
    }

    public function forceDelete(AuthUser $authUser, MiscellaneousExpense $miscellaneousExpense): bool
    {
        return $authUser->can('ForceDelete:MiscellaneousExpense');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MiscellaneousExpense');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MiscellaneousExpense');
    }

    public function replicate(AuthUser $authUser, MiscellaneousExpense $miscellaneousExpense): bool
    {
        return $authUser->can('Replicate:MiscellaneousExpense');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MiscellaneousExpense');
    }
}
