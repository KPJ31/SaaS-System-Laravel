<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    protected $fillable = ['company_id', 'timezone', 'currency', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function defaultAttendanceSettings(): array
    {
        return [
            'attendance_enabled' => true,
            'auto_absence_enabled' => true,
            'work_start_time' => '08:30',
            'work_end_time' => '17:00',
            'lunch_break_minutes' => 30,
            'late_grace_minutes' => 10,
            'early_check_in_allowance_minutes' => 30,
            'early_departure_grace_minutes' => 10,
            'full_day_minutes' => 480,
            'half_day_minutes' => 240,
            'working_days' => [1, 2, 3, 4, 5],
        ];
    }

    public function attendanceSettings(): array
    {
        return array_replace(self::defaultAttendanceSettings(), $this->settings['attendance'] ?? []);
    }
}
