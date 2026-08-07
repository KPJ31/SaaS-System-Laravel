<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyEvent extends Model
{
    use BelongsToCompany;

    public const TYPES = ['meeting', 'client_meeting', 'training', 'presentation', 'workshop', 'holiday', 'internal_deadline', 'other'];

    public const STATUSES = ['scheduled', 'cancelled'];

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'description',
        'event_type',
        'start_at',
        'end_at',
        'location',
        'meeting_link',
        'visibility',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
