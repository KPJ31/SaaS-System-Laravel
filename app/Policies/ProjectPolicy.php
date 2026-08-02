<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class ProjectPolicy
{
    use AuthorizesCompanyRecords;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'company_admin', 'employee'], true);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isSuperAdmin() || $this->companyAdmin($user, $project)) {
            return true;
        }

        return $user->role === 'employee'
            && $this->sameCompany($user, $project)
            && $project->users()->whereKey($user->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->companyAdmin($user, $project);
    }
}
