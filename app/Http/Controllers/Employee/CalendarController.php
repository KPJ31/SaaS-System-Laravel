<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Project;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(CalendarService $calendar): View
    {
        return view('calendar.index', [
            'rolePrefix' => 'employee',
            'eventsEndpoint' => route('employee.calendar.events'),
            'visualMap' => $calendar->visualMap(),
            'projects' => Project::where('company_id', $this->companyId())
                ->where(fn ($query) => $query
                    ->whereHas('users', fn ($team) => $team->where('users.id', auth()->id()))
                    ->orWhereHas('tasks', fn ($tasks) => $tasks->where('assignee_id', auth()->id())))
                ->orderBy('name')
                ->get(),
            'employees' => collect(),
            'manageEventsUrl' => null,
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
        ]);
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())
                ->whereKey($request->integer('project_id'))
                ->where(fn ($query) => $query
                    ->whereHas('users', fn ($team) => $team->where('users.id', auth()->id()))
                    ->orWhereHas('tasks', fn ($tasks) => $tasks->where('assignee_id', auth()->id())))
                ->exists(), 403);
        }
    }
}
