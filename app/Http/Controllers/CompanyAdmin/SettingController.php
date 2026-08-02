<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
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

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'timezone' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:10'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'default_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_instructions' => ['nullable', 'string', 'max:3000'],
            'default_project_status' => ['required', 'string', 'max:60'],
            'default_task_priority' => ['required', 'in:low,medium,high,urgent'],
            'email_notifications' => ['nullable', 'boolean'],
            'task_due_reminder' => ['nullable', 'boolean'],
            'payment_reminder' => ['nullable', 'boolean'],
        ]);

        CompanySetting::updateOrCreate(
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
                ],
            ]
        );

        $this->company()->update(['timezone' => $data['timezone']]);

        return back()->with('success', 'Company settings updated successfully.');
    }
}
