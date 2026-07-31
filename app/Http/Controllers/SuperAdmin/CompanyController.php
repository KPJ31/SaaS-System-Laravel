<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        return view('super-admin.companies.index', [
            'companies' => Company::withCount(['users', 'projects'])->latest()->paginate(10),
        ]);
    }

    public function updateStatus(Company $company, string $status): RedirectResponse
    {
        abort_unless(in_array($status, ['active', 'suspended'], true), 404);

        $company->update(['status' => $status]);

        return back()->with('success', 'Company status updated.');
    }
}
