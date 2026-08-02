<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'client_id',
        'created_by',
        'title',
        'service_type',
        'description',
        'status',
        'expected_start_date',
        'expected_end_date',
        'estimated_budget',
        'admin_note',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'converted_project_id',
    ];

    protected function casts(): array
    {
        return [
            'expected_start_date' => 'date',
            'expected_end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
