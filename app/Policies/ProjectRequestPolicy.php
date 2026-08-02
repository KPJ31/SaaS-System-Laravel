<?php

namespace App\Policies;

use App\Models\ProjectRequest;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class ProjectRequestPolicy
{
    use AuthorizesCompanyRecords;

    public function view(User $user, ProjectRequest $projectRequest): bool
    {
        return $user->isSuperAdmin() || $this->companyAdmin($user, $projectRequest);
    }

    public function update(User $user, ProjectRequest $projectRequest): bool
    {
        return $this->companyAdmin($user, $projectRequest);
    }
}
