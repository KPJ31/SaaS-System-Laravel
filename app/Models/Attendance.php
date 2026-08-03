<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'gross_minutes',
        'lunch_break_minutes',
        'net_work_minutes',
        'status',
        'is_late',
        'late_minutes',
        'is_early_departure',
        'early_departure_minutes',
        'note',
        'corrected_by',
        'correction_reason',
        'correction_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'is_late' => 'boolean',
            'is_early_departure' => 'boolean',
            'correction_snapshot' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
