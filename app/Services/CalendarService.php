<?php

namespace App\Services;

use App\Models\CompanyEvent;
use App\Models\LeaveRequest;
use App\Models\PersonalTodo;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    public const TYPES = ['company_event', 'project_start', 'project_deadline', 'task_deadline', 'leave', 'personal_todo'];

    public function eventsFor(User $user, Carbon $start, Carbon $end, array $filters = []): array
    {
        $types = $this->selectedTypes($filters['types'] ?? []);
        $events = collect();

        if (in_array('company_event', $types, true)) {
            $events = $events->merge($this->companyEvents($user, $start, $end));
        }

        if (in_array('project_start', $types, true) || in_array('project_deadline', $types, true)) {
            $events = $events->merge($this->projects($user, $start, $end, $types, $filters));
        }

        if (in_array('task_deadline', $types, true)) {
            $events = $events->merge($this->tasks($user, $start, $end, $filters));
        }

        if (in_array('leave', $types, true)) {
            $events = $events->merge($this->leave($user, $start, $end, $filters));
        }

        if (in_array('personal_todo', $types, true)) {
            $events = $events->merge($this->todos($user, $start, $end));
        }

        return $events
            ->sortBy([['starts_at', 'asc'], ['title', 'asc']])
            ->values()
            ->all();
    }

    public function upcomingFor(User $user, int $days = 14): array
    {
        $start = today();
        $end = today()->addDays($days)->endOfDay();

        return collect($this->eventsFor($user, $start, $end))
            ->take(8)
            ->values()
            ->all();
    }

    public function visualMap(): array
    {
        return [
            'company_event' => ['label' => 'Event', 'tone' => 'primary', 'icon' => 'fa-calendar-check'],
            'project_start' => ['label' => 'Project Start', 'tone' => 'info', 'icon' => 'fa-play'],
            'project_deadline' => ['label' => 'Project Due', 'tone' => 'danger', 'icon' => 'fa-flag-checkered'],
            'task_deadline' => ['label' => 'Task Due', 'tone' => 'warning', 'icon' => 'fa-list-check'],
            'leave' => ['label' => 'Leave', 'tone' => 'success', 'icon' => 'fa-user-clock'],
            'personal_todo' => ['label' => 'Todo', 'tone' => 'muted', 'icon' => 'fa-clipboard-list'],
        ];
    }

    private function companyEvents(User $user, Carbon $start, Carbon $end): Collection
    {
        return CompanyEvent::where('company_id', $user->company_id)
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(fn ($overlap) => $overlap->where('start_at', '<=', $start)->where('end_at', '>=', $end));
            })
            ->orderBy('start_at')
            ->get()
            ->map(fn (CompanyEvent $event): array => [
                'id' => 'company-event-'.$event->id,
                'source_id' => $event->id,
                'type' => 'company_event',
                'title' => $event->title,
                'starts_at' => $event->start_at->toIso8601String(),
                'ends_at' => $event->end_at?->toIso8601String(),
                'date' => $event->start_at->toDateString(),
                'status' => $event->status,
                'meta' => ucfirst(str_replace('_', ' ', $event->event_type)),
                'description' => $event->description,
                'location' => $event->location,
                'meeting_link' => $event->meeting_link,
                'url' => $user->role === 'company_admin' ? route('company-admin.company-events.show', $event) : null,
            ]);
    }

    private function projects(User $user, Carbon $start, Carbon $end, array $types, array $filters): Collection
    {
        $query = Project::where('company_id', $user->company_id)
            ->whereNotIn('status', ['cancelled'])
            ->when($user->role === 'employee', fn ($projectQuery) => $projectQuery->where(function ($scope) use ($user): void {
                $scope->whereHas('users', fn ($team) => $team->where('users.id', $user->id))
                    ->orWhereHas('tasks', fn ($tasks) => $tasks->where('assignee_id', $user->id));
            }))
            ->when(! empty($filters['project_id']), fn ($projectQuery) => $projectQuery->whereKey($filters['project_id']))
            ->where(function ($dateQuery) use ($start, $end, $types): void {
                if (in_array('project_start', $types, true)) {
                    $dateQuery->orWhereBetween('start_date', [$start->toDateString(), $end->toDateString()]);
                }

                if (in_array('project_deadline', $types, true)) {
                    $dateQuery->orWhereBetween('due_date', [$start->toDateString(), $end->toDateString()]);
                }
            });

        return $query->get()->flatMap(function (Project $project) use ($user, $types): array {
            $events = [];
            $url = route($user->role === 'employee' ? 'employee.projects.show' : 'company-admin.projects.show', $project);

            if ($project->start_date && in_array('project_start', $types, true)) {
                $events[] = [
                    'id' => 'project-start-'.$project->id,
                    'source_id' => $project->id,
                    'type' => 'project_start',
                    'title' => 'Project Start: '.$project->name,
                    'starts_at' => $project->start_date->startOfDay()->toIso8601String(),
                    'date' => $project->start_date->toDateString(),
                    'status' => $project->status,
                    'meta' => ucfirst($project->priority).' priority',
                    'url' => $url,
                ];
            }

            if ($project->due_date && in_array('project_deadline', $types, true)) {
                $events[] = [
                    'id' => 'project-due-'.$project->id,
                    'source_id' => $project->id,
                    'type' => 'project_deadline',
                    'title' => 'Project Due: '.$project->name,
                    'starts_at' => $project->due_date->startOfDay()->toIso8601String(),
                    'date' => $project->due_date->toDateString(),
                    'status' => $project->status,
                    'meta' => $project->due_date->isPast() && ! in_array($project->status, ['completed', 'cancelled'], true) ? 'Overdue' : ucfirst($project->priority).' priority',
                    'is_overdue' => $project->due_date->isPast() && ! in_array($project->status, ['completed', 'cancelled'], true),
                    'url' => $url,
                ];
            }

            return $events;
        });
    }

    private function tasks(User $user, Carbon $start, Carbon $end, array $filters): Collection
    {
        return Task::with('project')
            ->where('company_id', $user->company_id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['cancelled'])
            ->when($user->role === 'employee', fn ($query) => $query->where('assignee_id', $user->id))
            ->when(! empty($filters['project_id']), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->when($user->role === 'company_admin' && ! empty($filters['employee_id']), fn ($query) => $query->where('assignee_id', $filters['employee_id']))
            ->get()
            ->map(function (Task $task) use ($user): array {
                $isOverdue = app(TaskWorkflowService::class)->isOverdue($task);

                return [
                    'id' => 'task-due-'.$task->id,
                    'source_id' => $task->id,
                    'type' => 'task_deadline',
                    'title' => 'Task Due: '.$task->title,
                    'starts_at' => $task->due_date->startOfDay()->toIso8601String(),
                    'date' => $task->due_date->toDateString(),
                    'status' => $task->status,
                    'meta' => $isOverdue ? 'Overdue' : ($task->project?->name ?? 'Task'),
                    'is_overdue' => $isOverdue,
                    'url' => route($user->role === 'employee' ? 'employee.tasks.show' : 'company-admin.tasks.show', $task),
                ];
            });
    }

    private function leave(User $user, Carbon $start, Carbon $end, array $filters): Collection
    {
        return LeaveRequest::with('user:id,name')
            ->where('company_id', $user->company_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->when($user->role === 'employee', fn ($query) => $query->where('user_id', $user->id))
            ->when($user->role === 'company_admin' && ! empty($filters['employee_id']), fn ($query) => $query->where('user_id', $filters['employee_id']))
            ->get()
            ->map(fn (LeaveRequest $leave): array => [
                'id' => 'leave-'.$leave->id,
                'source_id' => $leave->id,
                'type' => 'leave',
                'title' => $user->role === 'company_admin' ? ($leave->user?->name.' on leave') : 'Approved leave',
                'starts_at' => $leave->start_date->startOfDay()->toIso8601String(),
                'ends_at' => $leave->end_date->endOfDay()->toIso8601String(),
                'date' => $leave->start_date->toDateString(),
                'status' => 'approved',
                'meta' => ucfirst($leave->leave_type),
                'url' => $user->role === 'company_admin' ? route('company-admin.leave-requests.show', $leave) : route('employee.leave-requests.index'),
            ]);
    }

    private function todos(User $user, Carbon $start, Carbon $end): Collection
    {
        return PersonalTodo::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'open')
            ->get()
            ->map(fn (PersonalTodo $todo): array => [
                'id' => 'todo-'.$todo->id,
                'source_id' => $todo->id,
                'type' => 'personal_todo',
                'title' => 'Todo: '.$todo->title,
                'starts_at' => $todo->due_date->startOfDay()->toIso8601String(),
                'date' => $todo->due_date->toDateString(),
                'status' => $todo->status,
                'meta' => ucfirst($todo->priority).' priority',
                'is_overdue' => $todo->isOverdue(),
                'url' => route($user->role === 'company_admin' ? 'company-admin.todos.index' : 'employee.todos.index'),
            ]);
    }

    private function selectedTypes(array|string|null $types): array
    {
        if (! is_array($types)) {
            $types = filled($types) ? explode(',', (string) $types) : self::TYPES;
        }

        $selected = array_values(array_intersect($types, self::TYPES));

        return $selected ?: self::TYPES;
    }
}
