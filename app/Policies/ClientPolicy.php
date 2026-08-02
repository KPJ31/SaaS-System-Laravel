<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class ClientPolicy
{
    use AuthorizesCompanyRecords;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'company_admin'], true);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->sameCompany($user, $client);
    }

    public function create(User $user): bool
    {
        return $user->role === 'company_admin' && $user->company_id !== null;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->companyAdmin($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->companyAdmin($user, $client);
    }
}
