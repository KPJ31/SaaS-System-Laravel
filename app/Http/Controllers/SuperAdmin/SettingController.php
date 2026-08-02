<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $keys = [
            'platform_name' => 'Elevanix',
            'platform_abbreviation' => 'EVX',
            'support_email' => 'support@example.com',
            'support_phone' => '',
            'platform_address' => '',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'platform_logo' => '',
            'favicon' => '',
            'primary_color' => '#6D28D9',
            'login_background_image' => '',
            'registration_enabled' => true,
            'company_approval_required' => true,
            'trial_duration_days' => 14,
            'currency' => 'USD',
            'subscription_reminder_days' => 7,
            'allow_trial_plan' => true,
            'allow_plan_upgrade' => true,
            'email_sender_name' => 'Elevanix',
            'email_sender_email' => config('mail.from.address'),
        ];

        $settings = collect($keys)->mapWithKeys(fn ($default, $key) => [$key => SystemSetting::getValue($key, $default)]);

        return view('super-admin.settings.index', ['settings' => $settings]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:255'],
            'platform_abbreviation' => ['nullable', 'string', 'max:20'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'platform_address' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', 'string', 'max:80'],
            'date_format' => ['required', 'string', 'max:30'],
            'platform_logo' => ['nullable', 'string', 'max:255'],
            'favicon' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'max:20'],
            'login_background_image' => ['nullable', 'string', 'max:255'],
            'registration_enabled' => ['nullable', 'boolean'],
            'company_approval_required' => ['nullable', 'boolean'],
            'trial_duration_days' => ['required', 'integer', 'min:0', 'max:365'],
            'currency' => ['required', 'string', 'max:10'],
            'subscription_reminder_days' => ['required', 'integer', 'min:1', 'max:90'],
            'allow_trial_plan' => ['nullable', 'boolean'],
            'allow_plan_upgrade' => ['nullable', 'boolean'],
            'email_sender_name' => ['required', 'string', 'max:255'],
            'email_sender_email' => ['nullable', 'email', 'max:255'],
        ]);

        foreach (['registration_enabled', 'company_approval_required', 'allow_trial_plan', 'allow_plan_upgrade'] as $key) {
            $data[$key] = $request->boolean($key);
        }

        foreach ($data as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
            SystemSetting::put($key, $value, $type, 'platform');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'settings_updated',
            'module' => 'System Settings',
            'description' => 'Updated platform settings',
            'new_values' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'System settings updated.');
    }
}
