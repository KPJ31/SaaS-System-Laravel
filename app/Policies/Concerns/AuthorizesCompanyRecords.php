<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesCompanyRecords
{
    protected function sameCompany(User $user, mixed $record): bool
    {
        return $user->isSuperAdmin()
            || ($user->company_id !== null && $user->company_id === $record->company_id);
    }

    protected function companyAdmin(User $user, mixed $record): bool
    {
        return $user->role === 'company_admin' && $this->sameCompany($user, $record);
    }
}
