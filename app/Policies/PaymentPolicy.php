<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCompanyRecords;

class PaymentPolicy
{
    use AuthorizesCompanyRecords;

    public function view(User $user, Payment $payment): bool
    {
        return $user->isSuperAdmin() || $this->companyAdmin($user, $payment);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->companyAdmin($user, $payment);
    }
}
