<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\CompanyEvent;
use App\Models\User;
use App\Notifications\CompanyEventNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyEventController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        abort_unless(! $request->filled('status') || in_array($request->status, CompanyEvent::STATUSES, true), 404);
        abort_unless(! $request->filled('event_type') || in_array($request->event_type, CompanyEvent::TYPES, true), 404);

        $events = CompanyEvent::with('creator')
            ->where('company_id', $this->companyId())
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->event_type, fn ($query, $type) => $query->where('event_type', $type))
            ->when($request->search, fn ($query, $search) => $query->where(fn ($scope) => $scope
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")))
            ->orderByDesc('start_at')
            ->paginate(12)
            ->withQueryString();

        return view('company-admin.company-events.index', [
            'events' => $events,
            'types' => CompanyEvent::TYPES,
            'summary' => [
                'scheduled' => CompanyEvent::where('company_id', $this->companyId())->where('status', 'scheduled')->count(),
                'cancelled' => CompanyEvent::where('company_id', $this->companyId())->where('status', 'cancelled')->count(),
                'upcoming' => CompanyEvent::where('company_id', $this->companyId())->where('status', 'scheduled')->where('start_at', '>=', now())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('company-admin.company-events.form', ['event' => new CompanyEvent(), 'types' => CompanyEvent::TYPES]);
    }

    public function store(Request $request, AuditLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $event = CompanyEvent::create($data + [
            'company_id' => $this->companyId(),
            'created_by' => auth()->id(),
            'visibility' => 'company',
            'status' => 'scheduled',
        ]);

        $logger->record('company_event_created', 'Company event created.', auth()->user(), $event, $this->companyId(), request: $request);
        $this->notifyCompany($event, 'Company event scheduled', $event->title.' is scheduled for '.$event->start_at->format('M d, Y H:i').'.');

        return redirect()->route('company-admin.company-events.show', $event)->with('success', 'Company event created.');
    }

    public function show(CompanyEvent $companyEvent): View
    {
        $this->abortUnlessCompanyRecord($companyEvent);

        return view('company-admin.company-events.show', ['event' => $companyEvent->load('creator')]);
    }

    public function edit(CompanyEvent $companyEvent): View
    {
        $this->abortUnlessCompanyRecord($companyEvent);

        return view('company-admin.company-events.form', ['event' => $companyEvent, 'types' => CompanyEvent::TYPES]);
    }

    public function update(Request $request, CompanyEvent $companyEvent, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($companyEvent);
        $data = $this->validated($request);
        $old = $companyEvent->only(['title', 'event_type', 'start_at', 'end_at', 'location', 'meeting_link', 'status']);
        $companyEvent->update($data);
        $logger->record('company_event_updated', 'Company event updated.', auth()->user(), $companyEvent, $this->companyId(), ['old' => $old], $request);
        $this->notifyCompany($companyEvent->fresh(), 'Company event updated', $companyEvent->title.' was updated.');

        return redirect()->route('company-admin.company-events.show', $companyEvent)->with('success', 'Company event updated.');
    }

    public function cancel(Request $request, CompanyEvent $companyEvent, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($companyEvent);
        abort_if($companyEvent->status === 'cancelled', 422, 'Event is already cancelled.');

        $companyEvent->update(['status' => 'cancelled']);
        $logger->record('company_event_cancelled', 'Company event cancelled.', auth()->user(), $companyEvent, $this->companyId(), request: $request);
        $this->notifyCompany($companyEvent->fresh(), 'Company event cancelled', $companyEvent->title.' has been cancelled.');

        return back()->with('success', 'Company event cancelled.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_type' => ['required', Rule::in(CompanyEvent::TYPES)],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
        ]);
    }

    private function notifyCompany(CompanyEvent $event, string $title, string $message): void
    {
        $recipients = User::where('company_id', $this->companyId())
            ->where('status', 'active')
            ->whereKeyNot(auth()->id())
            ->whereIn('role', ['company_admin', 'employee'])
            ->get();

        Notification::send($recipients, new CompanyEventNotification($event, $title, $message));
    }
}
