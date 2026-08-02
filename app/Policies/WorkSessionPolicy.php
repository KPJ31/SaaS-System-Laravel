<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkSession;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class WorkSessionPolicy
{
    use AuthorizesCompanyRecords;

    public function view(User $user, WorkSession $workSession): bool
    {
        return $user->isSuperAdmin()
            || $this->companyAdmin($user, $workSession)
            || ($user->role === 'employee'
                && $this->sameCompany($user, $workSession)
                && $workSession->user_id === $user->id);
    }

    public function update(User $user, WorkSession $workSession): bool
    {
        return $user->role === 'employee'
            && $this->sameCompany($user, $workSession)
            && $workSession->user_id === $user->id
            && $workSession->ended_at === null;
    }
}
