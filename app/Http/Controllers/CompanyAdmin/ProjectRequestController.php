<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectRequestController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $this->authorizeFilters($request);

        $baseQuery = ProjectRequest::where('company_id', $this->companyId());

        $projectRequests = ProjectRequest::with(['client', 'convertedProject'])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('service_type', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"))))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.project-requests.index', [
            'projectRequests' => $projectRequests,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(['id', 'name', 'company_name']),
            'summary' => [
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'under_review' => (clone $baseQuery)->where('status', 'under_review')->count(),
                'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
                'converted' => (clone $baseQuery)->where('status', 'converted_to_project')->count(),
            ],
        ]);
    }

    public function show(ProjectRequest $projectRequest): View
    {
        $this->abortUnlessCompanyRecord($projectRequest);

        return view('company-admin.project-requests.show', [
            'projectRequest' => $projectRequest->load(['client', 'creator', 'approver', 'convertedProject']),
            'activityLogs' => AuditLog::with('user')
                ->where('company_id', $this->companyId())
                ->where('auditable_type', ProjectRequest::class)
                ->where('auditable_id', $projectRequest->id)
                ->latest()
                ->take(8)
                ->get(),
            'validStatuses' => $this->validNextStatuses($projectRequest->status),
        ]);
    }

    public function update(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);

        $data = $request->validate([
            'status' => ['required', 'in:draft,pending,under_review,approved,rejected,payment_requested,payment_confirmed,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        if (! $this->canTransition($projectRequest->status, $data['status'])) {
            return back()->with('error', 'This request cannot be changed from '.$projectRequest->status.' to '.$data['status'].'.');
        }

        $projectRequest->update($data);

        return back()->with('success', 'Project request updated.');
    }

    public function approve(ProjectRequest $projectRequest, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);

        if (! $this->canTransition($projectRequest->status, 'approved')) {
            return back()->with('error', 'Only pending or under-review requests can be approved.');
        }

        DB::transaction(function () use ($projectRequest, $logger): void {
            $projectRequest->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
            $logger->record('project_request_approved', 'Project request approved.', auth()->user(), $projectRequest, $this->companyId(), request: request());
        });

        return back()->with('success', 'Project request approved.');
    }

    public function reject(Request $request, ProjectRequest $projectRequest, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        if (! $this->canTransition($projectRequest->status, 'rejected')) {
            return back()->with('error', 'Only pending or under-review requests can be rejected.');
        }

        DB::transaction(function () use ($request, $projectRequest, $data, $logger): void {
            $projectRequest->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
            $logger->record('project_request_rejected', 'Project request rejected.', auth()->user(), $projectRequest, $this->companyId(), ['reason' => $data['rejection_reason']], $request);
        });

        return back()->with('success', 'Project request rejected.');
    }

    public function convertToProject(ProjectRequest $projectRequest, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);
        abort_unless(in_array($projectRequest->status, ['approved', 'payment_confirmed'], true), 422);
        abort_if($projectRequest->converted_project_id || $projectRequest->convertedProject()->exists(), 422, 'This request has already been converted to a project.');
        abort_if(Project::where('company_id', $this->companyId())->where('project_request_id', $projectRequest->id)->exists(), 422, 'This request has already been converted to a project.');

        if ($this->subscriptionLimitReached('project_limit', Project::class)) {
            return back()->with('error', $this->limitMessage());
        }

        $project = DB::transaction(function () use ($projectRequest, $logger): Project {
            $project = Project::create([
                'company_id' => $this->companyId(),
                'client_id' => $projectRequest->client_id,
                'project_request_id' => $projectRequest->id,
                'name' => $projectRequest->title,
                'description' => $projectRequest->description,
                'status' => 'planning',
                'due_date' => $projectRequest->expected_end_date,
                'budget' => $projectRequest->estimated_budget,
                'progress' => 0,
                'priority' => 'medium',
            ]);

            $projectRequest->update(['status' => 'converted_to_project', 'converted_project_id' => $project->id]);
            $logger->record('project_request_converted', 'Project request converted to project.', auth()->user(), $projectRequest, $this->companyId(), ['project_id' => $project->id], request());

            return $project;
        });

        return redirect()->route('company-admin.projects.show', $project)->with('success', 'Project request converted to project.');
    }

    private function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        return match ($currentStatus) {
            'draft' => $newStatus === 'pending',
            'pending' => in_array($newStatus, ['under_review', 'approved', 'rejected', 'cancelled'], true),
            'under_review' => in_array($newStatus, ['approved', 'rejected'], true),
            'approved' => $newStatus === 'payment_requested',
            'payment_requested' => $newStatus === 'payment_confirmed',
            'payment_confirmed' => false,
            default => false,
        };
    }

    private function validNextStatuses(string $currentStatus): array
    {
        return match ($currentStatus) {
            'draft' => ['pending'],
            'pending' => ['under_review', 'approved', 'rejected', 'cancelled'],
            'under_review' => ['approved', 'rejected'],
            'approved' => ['payment_requested'],
            'payment_requested' => ['payment_confirmed'],
            'payment_confirmed' => [],
            default => [],
        };
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('client_id')) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($request->integer('client_id'))->exists(), 403);
        }
    }
}
