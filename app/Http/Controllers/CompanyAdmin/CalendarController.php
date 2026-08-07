<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    use HandlesCompanyAccess;

    public function index(CalendarService $calendar): View
    {
        return view('calendar.index', [
            'rolePrefix' => 'company-admin',
            'eventsEndpoint' => route('company-admin.calendar.events'),
            'visualMap' => $calendar->visualMap(),
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
            'manageEventsUrl' => route('company-admin.company-events.index'),
        ]);
    }

    public function events(Request $request, CalendarService $calendar): JsonResponse
    {
        $data = $this->validated($request);
        $this->authorizeFilters($request);

        return response()->json([
            'events' => $calendar->eventsFor(auth()->user(), Carbon::parse($data['start'])->startOfDay(), Carbon::parse($data['end'])->endOfDay(), [
                'types' => $request->input('types', []),
                'project_id' => $request->integer('project_id') ?: null,
                'employee_id' => $request->integer('employee_id') ?: null,
            ]),
            'visualMap' => $calendar->visualMap(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'in:'.implode(',', CalendarService::TYPES)],
            'project_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
        ]);
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }

        if ($request->filled('employee_id')) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($request->integer('employee_id'))->exists(), 403);
        }
    }
}
