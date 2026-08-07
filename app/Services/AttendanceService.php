<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public const STATUSES = ['present', 'late', 'half_day', 'absent', 'on_leave', 'not_checked_in'];

    public function settingsForCompany(int $companyId, ?Company $company = null): array
    {
        $setting = CompanySetting::firstOrCreate(
            ['company_id' => $companyId],
            ['timezone' => $company?->timezone ?? 'UTC', 'currency' => 'USD', 'settings' => []]
        );

        return $setting->attendanceSettings() + ['timezone' => $setting->timezone ?: 'UTC'];
    }

    public function todayDate(array $settings): string
    {
        return now($settings['timezone'])->toDateString();
    }

    public function todayAttendance(User $user, array $settings): ?Attendance
    {
        return Attendance::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $this->todayDate($settings))
            ->first();
    }

    public function checkIn(User $user): Attendance
    {
        $settings = $this->settingsForCompany((int) $user->company_id, $user->company);
        $now = now($settings['timezone']);
        $date = $now->toDateString();

        $this->ensureAttendanceAllowed($settings, $now);

        if ($this->approvedLeaveExists($user, $date)) {
            throw ValidationException::withMessages(['attendance' => 'You have approved leave for today. Attendance check-in is not required.']);
        }

        if (Attendance::where('company_id', $user->company_id)->where('user_id', $user->id)->whereDate('attendance_date', $date)->exists()) {
            throw ValidationException::withMessages(['attendance' => 'You have already checked in for today.']);
        }

        $start = Carbon::parse($date.' '.$settings['work_start_time'], $settings['timezone']);
        $allowedArrival = $start->copy()->addMinutes((int) $settings['late_grace_minutes']);
        $isLate = $now->gt($allowedArrival);

        return Attendance::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'attendance_date' => $date,
            'check_in_time' => $now,
            'status' => $isLate ? 'late' : 'present',
            'is_late' => $isLate,
            'late_minutes' => $isLate ? $start->diffInMinutes($now) : 0,
        ]);
    }

    public function checkOut(User $user, ?string $note = null): Attendance
    {
        $settings = $this->settingsForCompany((int) $user->company_id, $user->company);
        $now = now($settings['timezone']);
        $date = $now->toDateString();

        $attendance = Attendance::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if (! $attendance || ! $attendance->check_in_time) {
            throw ValidationException::withMessages(['attendance' => 'Please check in before checking out.']);
        }

        if ($attendance->check_out_time) {
            throw ValidationException::withMessages(['attendance' => 'You have already checked out for today.']);
        }

        $checkIn = $attendance->check_in_time->timezone($settings['timezone']);

        if ($now->lte($checkIn)) {
            throw ValidationException::withMessages(['attendance' => 'Check-out time must be after check-in time.']);
        }

        $summary = $this->calculateSummary($checkIn, $now, $settings);
        $attendance->update($summary + [
            'check_out_time' => $now,
            'note' => $note ?? $attendance->note,
        ]);

        return $attendance->refresh();
    }

    public function calculateSummary(Carbon $checkIn, Carbon $checkOut, array $settings): array
    {
        $gross = max(1, $checkIn->diffInMinutes($checkOut));
        $lunch = $gross >= 300 ? (int) $settings['lunch_break_minutes'] : 0;
        $net = max(0, $gross - $lunch);
        $start = Carbon::parse($checkIn->toDateString().' '.$settings['work_start_time'], $settings['timezone']);
        $end = Carbon::parse($checkIn->toDateString().' '.$settings['work_end_time'], $settings['timezone']);
        $allowedArrival = $start->copy()->addMinutes((int) $settings['late_grace_minutes']);
        $earlyLimit = $end->copy()->subMinutes((int) $settings['early_departure_grace_minutes']);
        $isLate = $checkIn->gt($allowedArrival);
        $isEarly = $checkOut->lt($earlyLimit);

        return [
            'gross_minutes' => $gross,
            'lunch_break_minutes' => $lunch,
            'net_work_minutes' => $net,
            'status' => $net >= (int) $settings['full_day_minutes'] ? 'present' : 'half_day',
            'is_late' => $isLate,
            'late_minutes' => $isLate ? $start->diffInMinutes($checkIn) : 0,
            'is_early_departure' => $isEarly,
            'early_departure_minutes' => $isEarly ? $checkOut->diffInMinutes($end) : 0,
        ];
    }

    public function approvedLeaveExists(User $user, string $date): bool
    {
        return LeaveRequest::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    public function companyDayStats(int $companyId, string $date): array
    {
        $employeeCount = User::where('company_id', $companyId)->where('role', 'employee')->count();
        $today = Attendance::where('company_id', $companyId)->whereDate('attendance_date', $date);

        return [
            'employees' => $employeeCount,
            'checked_in' => (clone $today)->whereNotNull('check_in_time')->count(),
            'present' => (clone $today)->where('status', 'present')->count(),
            'late' => (clone $today)->where('is_late', true)->count(),
            'half_day' => (clone $today)->where('status', 'half_day')->count(),
            'absent' => (clone $today)->where('status', 'absent')->count(),
            'on_leave' => (clone $today)->where('status', 'on_leave')->count(),
            'checked_out' => (clone $today)->whereNotNull('check_out_time')->count(),
            'not_checked_in' => max(0, $employeeCount - (clone $today)->count()),
        ];
    }

    private function ensureAttendanceAllowed(array $settings, Carbon $now): void
    {
        if (! $settings['attendance_enabled']) {
            throw ValidationException::withMessages(['attendance' => 'Attendance tracking is disabled for your company.']);
        }

        if (! in_array($now->dayOfWeekIso, array_map('intval', $settings['working_days']), true)) {
            throw ValidationException::withMessages(['attendance' => 'Today is not configured as a working day.']);
        }
    }
}
