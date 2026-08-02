<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'trial_ends_at',
        'renews_at',
        'ends_at',
        'monthly_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'trial_ends_at' => 'date',
            'renews_at' => 'date',
            'ends_at' => 'date',
            'monthly_price' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
