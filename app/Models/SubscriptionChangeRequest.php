<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionChangeRequest extends Model
{
    public const ACTIVE_STATUSES = ['pending', 'payment_required', 'payment_submitted', 'under_review', 'approved'];

    protected $fillable = [
        'company_id',
        'current_subscription_id',
        'current_plan_id',
        'requested_plan_id',
        'requested_by',
        'change_type',
        'current_price',
        'requested_price',
        'payable_amount',
        'billing_cycle',
        'effective_date',
        'status',
        'request_note',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'payment_id',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'requested_price' => 'decimal:2',
            'payable_amount' => 'decimal:2',
            'effective_date' => 'date',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'current_subscription_id');
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'current_plan_id');
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'requested_plan_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'payment_required'], true);
    }

    public function requiresPayment(): bool
    {
        return (float) $this->payable_amount > 0;
    }
}
