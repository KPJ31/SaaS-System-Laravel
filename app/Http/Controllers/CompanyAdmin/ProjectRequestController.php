<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectRequestController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $projectRequests = ProjectRequest::with('client')
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.project-requests.index', compact('projectRequests'));
    }

    public function show(ProjectRequest $projectRequest): View
    {
        $this->abortUnlessCompanyRecord($projectRequest);

        return view('company-admin.project-requests.show', ['projectRequest' => $projectRequest->load(['client', 'creator', 'approver'])]);
    }

    public function update(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);

        $data = $request->validate([
            'status' => ['required', 'in:draft,pending,under_review,approved,rejected,payment_requested,payment_confirmed,converted_to_project,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $projectRequest->update($data);

        return back()->with('success', 'Project request updated.');
    }

    public function approve(ProjectRequest $projectRequest): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);
        $projectRequest->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);

        return back()->with('success', 'Project request approved.');
    }

    public function reject(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $projectRequest->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);

        return back()->with('success', 'Project request rejected.');
    }

    public function convertToProject(ProjectRequest $projectRequest): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($projectRequest);
        abort_unless(in_array($projectRequest->status, ['approved', 'payment_confirmed'], true), 422);

        if ($this->subscriptionLimitReached('project_limit', Project::class)) {
            return back()->with('error', $this->limitMessage());
        }

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

        return redirect()->route('company-admin.projects.show', $project)->with('success', 'Project request converted to project.');
    }
}
