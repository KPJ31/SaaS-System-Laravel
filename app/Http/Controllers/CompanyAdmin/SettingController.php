<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    use HandlesCompanyAccess;

    public function index(): View
    {
        $setting = CompanySetting::firstOrCreate(
            ['company_id' => $this->companyId()],
            ['timezone' => $this->company()->timezone ?? 'UTC', 'currency' => 'USD', 'settings' => []]
        );

        return view('company-admin.settings.index', compact('setting'));
    }

    public function update(Request $request, AuditLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'alpha', 'size:3'],
            'invoice_prefix' => ['nullable', 'alpha_dash', 'max:20'],
            'default_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_instructions' => ['nullable', 'string', 'max:3000'],
            'default_project_status' => ['required', 'in:planning,pending,approved,active,in_progress,on_hold,testing,completed,cancelled'],
            'default_task_priority' => ['required', 'in:low,medium,high,urgent'],
            'email_notifications' => ['nullable', 'boolean'],
            'task_due_reminder' => ['nullable', 'boolean'],
            'payment_reminder' => ['nullable', 'boolean'],
            'attendance_enabled' => ['nullable', 'boolean'],
            'auto_absence_enabled' => ['nullable', 'boolean'],
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i', 'after:work_start_time'],
            'lunch_break_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'early_check_in_allowance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'early_departure_grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'full_day_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'half_day_minutes' => ['required', 'integer', 'min:1', 'lte:full_day_minutes'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7'],
        ]);

        $data['currency'] = strtoupper($data['currency']);

        $setting = CompanySetting::updateOrCreate(
            ['company_id' => $this->companyId()],
            [
                'timezone' => $data['timezone'],
                'currency' => $data['currency'],
                'settings' => [
                    'invoice_prefix' => $data['invoice_prefix'] ?? 'INV',
                    'default_tax_percentage' => $data['default_tax_percentage'] ?? 0,
                    'payment_instructions' => $data['payment_instructions'] ?? null,
                    'default_project_status' => $data['default_project_status'],
                    'default_task_priority' => $data['default_task_priority'],
                    'email_notifications' => (bool) ($data['email_notifications'] ?? false),
                    'task_due_reminder' => (bool) ($data['task_due_reminder'] ?? false),
                    'payment_reminder' => (bool) ($data['payment_reminder'] ?? false),
                    'attendance' => [
                        'attendance_enabled' => (bool) ($data['attendance_enabled'] ?? false),
                        'auto_absence_enabled' => (bool) ($data['auto_absence_enabled'] ?? false),
                        'work_start_time' => $data['work_start_time'],
                        'work_end_time' => $data['work_end_time'],
                        'lunch_break_minutes' => (int) $data['lunch_break_minutes'],
                        'late_grace_minutes' => (int) $data['late_grace_minutes'],
                        'early_check_in_allowance_minutes' => (int) $data['early_check_in_allowance_minutes'],
                        'early_departure_grace_minutes' => (int) $data['early_departure_grace_minutes'],
                        'full_day_minutes' => (int) $data['full_day_minutes'],
                        'half_day_minutes' => (int) $data['half_day_minutes'],
                        'working_days' => array_map('intval', $data['working_days']),
                    ],
                ],
            ]
        );

        $this->company()->update(['timezone' => $data['timezone']]);
        $logger->record('company_attendance_settings_updated', 'Company working-hours settings updated.', auth()->user(), $setting, $this->companyId(), request: $request);

        return back()->with('success', 'Company settings updated successfully.');
    }
}
