<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class InvoicePolicy
{
    use AuthorizesCompanyRecords;

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isSuperAdmin() || $this->companyAdmin($user, $invoice);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->companyAdmin($user, $invoice);
    }
}
