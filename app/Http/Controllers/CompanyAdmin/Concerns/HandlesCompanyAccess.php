<?php

namespace App\Http\Controllers\CompanyAdmin\Concerns;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;

trait HandlesCompanyAccess
{
    protected function companyId(): int
    {
        return (int) auth()->user()->company_id;
    }

    protected function company(): Company
    {
        return auth()->user()->company;
    }

    protected function abortUnlessCompanyRecord(Model $model): void
    {
        if ((int) $model->company_id !== $this->companyId()) {
            abort(403);
        }
    }

    protected function subscriptionLimitReached(string $limitColumn, string $modelClass, ?array $extraWhere = null): bool
    {
        $plan = $this->company()->activeSubscription?->plan;

        if (! $plan instanceof SubscriptionPlan || ! isset($plan->{$limitColumn})) {
            return false;
        }

        $limit = (int) $plan->{$limitColumn};

        if ($limit <= 0) {
            return false;
        }

        $query = $modelClass::where('company_id', $this->companyId());

        foreach ($extraWhere ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        return $query->count() >= $limit;
    }

    protected function limitMessage(): string
    {
        return 'You have reached the limit of your current subscription plan.';
    }
}
