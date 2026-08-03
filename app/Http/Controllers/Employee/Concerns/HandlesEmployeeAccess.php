<?php

namespace App\Http\Controllers\Employee\Concerns;

use App\Models\Project;
use App\Models\Task;

trait HandlesEmployeeAccess
{
    protected function employee()
    {
        return auth()->user();
    }

    protected function companyId(): int
    {
        return (int) auth()->user()->company_id;
    }

    protected function abortUnlessOwnTask(Task $task): void
    {
        abort_unless($task->company_id === $this->companyId() && (int) $task->assignee_id === (int) auth()->id(), 403);
    }

    protected function abortUnlessAssignedProject(Project $project): void
    {
        $assigned = $project->users()->where('users.id', auth()->id())->exists()
            || $project->tasks()->where('assignee_id', auth()->id())->exists();

        abort_unless($project->company_id === $this->companyId() && $assigned, 403);
    }

    protected function activeTimer()
    {
        return auth()->user()->workSessions()
            ->with(['project', 'task'])
            ->where('company_id', $this->companyId())
            ->where('status', 'running')
            ->whereNull('ended_at')
            ->latest()
            ->first();
    }
}
