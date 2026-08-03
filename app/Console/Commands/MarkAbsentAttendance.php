<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentAttendance extends Command
{
    protected $signature = 'attendance:mark-absent {--date=}';

    protected $description = 'Mark absent or on-leave attendance records for active company Employees.';

    public function handle(): int
    {
        $created = 0;

        Company::where('status', 'active')->with('setting')->chunkById(50, function ($companies) use (&$created): void {
            foreach ($companies as $company) {
                $setting = $company->setting ?: CompanySetting::firstOrCreate(
                    ['company_id' => $company->id],
                    ['timezone' => $company->timezone ?? 'UTC', 'currency' => 'USD', 'settings' => []]
                );
                $settings = $setting->attendanceSettings() + ['timezone' => $setting->timezone ?: 'UTC'];

                if (! $settings['attendance_enabled'] || ! $settings['auto_absence_enabled']) {
                    continue;
                }

                $date = $this->option('date') ?: now($settings['timezone'])->subDay()->toDateString();
                $day = Carbon::parse($date, $settings['timezone'])->dayOfWeekIso;

                if (! in_array($day, array_map('intval', $settings['working_days']), true)) {
                    continue;
                }

                User::where('company_id', $company->id)->where('role', 'employee')->where('status', 'active')->each(function (User $employee) use ($company, $date, &$created): void {
                    if (Attendance::where('company_id', $company->id)->where('user_id', $employee->id)->whereDate('attendance_date', $date)->exists()) {
                        return;
                    }

                    $onLeave = LeaveRequest::where('company_id', $company->id)
                        ->where('user_id', $employee->id)
                        ->where('status', 'approved')
                        ->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date)
                        ->exists();

                    Attendance::create([
                        'company_id' => $company->id,
                        'user_id' => $employee->id,
                        'attendance_date' => $date,
                        'status' => $onLeave ? 'on_leave' : 'absent',
                        'note' => $onLeave ? 'Approved leave found for this date.' : 'Automatically marked absent.',
                    ]);

                    $created++;
                });
            }
        });

        $this->info("Attendance records created: {$created}");

        return self::SUCCESS;
    }
}
