<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\LeaveRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(): View
    {
        return view('employee.leave-requests.index', [
            'leaves' => LeaveRequest::where('company_id', $this->companyId())->where('user_id', auth()->id())->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('employee.leave-requests.form', ['leave' => new LeaveRequest()]);
    }

    public function store(Request $request, AuditLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $this->preventOverlap($data);
        $leave = LeaveRequest::create($data + [
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'total_days' => now()->parse($data['start_date'])->diffInDays(now()->parse($data['end_date'])) + 1,
            'status' => 'pending',
        ]);
        $logger->record('leave_requested', 'Leave request submitted.', auth()->user(), $leave, $this->companyId(), request: $request);

        return redirect()->route('employee.leave-requests.index')->with('success', 'Leave request submitted.');
    }

    public function edit(LeaveRequest $leaveRequest): View
    {
        $this->abortUnlessOwnPendingLeave($leaveRequest);

        return view('employee.leave-requests.form', ['leave' => $leaveRequest]);
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->abortUnlessOwnPendingLeave($leaveRequest);
        $data = $this->validated($request);
        $this->preventOverlap($data, $leaveRequest->id);
        $leaveRequest->update($data + ['total_days' => now()->parse($data['start_date'])->diffInDays(now()->parse($data['end_date'])) + 1]);

        return redirect()->route('employee.leave-requests.index')->with('success', 'Leave request updated.');
    }

    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->abortUnlessOwnPendingLeave($leaveRequest);
        $leaveRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Leave request cancelled.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'leave_type' => ['required', 'in:annual,casual,sick,unpaid,emergency,other'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function abortUnlessOwnPendingLeave(LeaveRequest $leave): void
    {
        abort_unless($leave->company_id === $this->companyId() && (int) $leave->user_id === (int) auth()->id() && $leave->status === 'pending', 403);
    }

    private function preventOverlap(array $data, ?int $ignoreId = null): void
    {
        $exists = LeaveRequest::where('company_id', $this->companyId())->where('user_id', auth()->id())->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();

        abort_if($exists, 422, 'You already have a leave request for these dates.');
    }
}
