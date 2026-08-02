<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class TaskPolicy
{
    use AuthorizesCompanyRecords;

    public function view(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin() || $this->companyAdmin($user, $task)) {
            return true;
        }

        return $user->role === 'employee'
            && $this->sameCompany($user, $task)
            && $task->assignee_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        if ($this->companyAdmin($user, $task)) {
            return true;
        }

        return $user->role === 'employee'
            && $this->sameCompany($user, $task)
            && $task->assignee_id === $user->id;
    }
}
