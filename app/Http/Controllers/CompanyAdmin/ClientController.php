<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $clients = Client::withCount(['projects', 'projectRequests', 'payments', 'invoices'])
            ->withCount([
                'projects as active_projects_count' => fn ($query) => $query->whereIn('status', ['active', 'in_progress', 'testing']),
                'invoices as unpaid_invoices_count' => fn ($query) => $query->whereIn('status', ['sent', 'unpaid', 'overdue']),
            ])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('company-admin.clients.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $this->companyId();

        $client = Client::create($data);
        $this->auditClient($client, 'client_created', 'Client created.');

        return redirect()->route('company-admin.clients.show', $client)->with('success', 'Client created successfully.');
    }

    public function show(Client $client): View
    {
        $this->abortUnlessCompanyRecord($client);

        return view('company-admin.clients.show', [
            'client' => $client->loadCount([
                'projects',
                'projectRequests',
                'payments',
                'invoices',
                'projects as active_projects_count' => fn ($query) => $query->whereIn('status', ['active', 'in_progress', 'testing']),
                'invoices as unpaid_invoices_count' => fn ($query) => $query->whereIn('status', ['sent', 'unpaid', 'overdue']),
            ]),
            'projects' => Project::where('company_id', $this->companyId())
                ->where('client_id', $client->id)
                ->withCount('tasks')
                ->latest()
                ->take(8)
                ->get(),
            'projectRequests' => ProjectRequest::where('company_id', $this->companyId())
                ->where('client_id', $client->id)
                ->latest()
                ->take(5)
                ->get(),
            'invoices' => Invoice::where('company_id', $this->companyId())
                ->where('client_id', $client->id)
                ->latest()
                ->take(5)
                ->get(),
            'payments' => Payment::where('company_id', $this->companyId())
                ->where('client_id', $client->id)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->abortUnlessCompanyRecord($client);

        return view('company-admin.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($client);
        $client->update($this->validated($request));
        $this->auditClient($client, 'client_updated', 'Client updated.');

        return redirect()->route('company-admin.clients.show', $client)->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($client);
        $client->update(['status' => 'inactive']);
        $this->auditClient($client, 'client_deactivated', 'Client deactivated.');

        return redirect()->route('company-admin.clients.index')->with('success', 'Client deactivated.');
    }

    public function updateStatus(Client $client, string $status): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($client);
        abort_unless(in_array($status, ['active', 'inactive', 'blocked'], true), 404);
        $client->update(['status' => $status]);
        $this->auditClient($client, 'client_status_updated', 'Client status updated to '.$status.'.');

        return back()->with('success', 'Client status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive,blocked'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function auditClient(Client $client, string $action, string $description): void
    {
        AuditLog::create([
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => 'clients',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'description' => $description.' '.$client->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
