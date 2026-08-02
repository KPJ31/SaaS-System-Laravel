<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $leaves = LeaveRequest::with(['user', 'reviewer'])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->leave_type, fn ($query, $type) => $query->where('leave_type', $type))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.leave-requests.index', [
            'leaves' => $leaves,
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
        ]);
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $this->abortUnlessCompanyRecord($leaveRequest);

        return view('company-admin.leave-requests.show', ['leave' => $leaveRequest->load(['user', 'reviewer'])]);
    }

    public function review(Request $request, LeaveRequest $leaveRequest, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($leaveRequest);
        abort_unless($leaveRequest->status === 'pending', 403);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leaveRequest->update([
            'status' => $data['status'],
            'reviewed_by' => auth()->id(),
            'review_note' => $data['review_note'] ?? null,
            'reviewed_at' => now(),
        ]);
        $logger->record('leave_'.$data['status'], 'Leave request '.$data['status'].'.', auth()->user(), $leaveRequest, $this->companyId(), request: $request);

        return back()->with('success', 'Leave request '.$data['status'].'.');
    }
}
