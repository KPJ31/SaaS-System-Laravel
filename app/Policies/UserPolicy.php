<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $employee): bool
    {
        return $actor->isSuperAdmin()
            || ($actor->role === 'company_admin'
                && $employee->role === 'employee'
                && $actor->company_id !== null
                && $actor->company_id === $employee->company_id)
            || $actor->id === $employee->id;
    }

    public function update(User $actor, User $employee): bool
    {
        return $actor->role === 'company_admin'
            && $employee->role === 'employee'
            && $actor->company_id !== null
            && $actor->company_id === $employee->company_id;
    }
}
