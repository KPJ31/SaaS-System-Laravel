<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    public function scopeForUserCompany(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.company_id', $user->company_id);
    }
}
