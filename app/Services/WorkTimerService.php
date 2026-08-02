<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkTimerService
{
    public function start(User $user, ?Project $project = null, ?Task $task = null, ?string $notes = null): WorkSession
    {
        if ($user->company_id === null) {
            throw ValidationException::withMessages(['timer' => 'Only company users can track work time.']);
        }

        if ($project && $project->company_id !== $user->company_id) {
            throw ValidationException::withMessages(['project_id' => 'The selected project is not available.']);
        }

        if ($task && ($task->company_id !== $user->company_id || ($project && $task->project_id !== $project->id))) {
            throw ValidationException::withMessages(['task_id' => 'The selected task is not available.']);
        }

        return DB::transaction(function () use ($user, $project, $task, $notes): WorkSession {
            $active = WorkSession::query()
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->exists();

            if ($active) {
                throw ValidationException::withMessages(['timer' => 'Stop the active work timer before starting another one.']);
            }

            return WorkSession::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'project_id' => $project?->id,
                'task_id' => $task?->id,
                'started_at' => now(),
                'notes' => $notes,
            ]);
        });
    }

    public function stop(User $user, WorkSession $workSession, ?string $notes = null): WorkSession
    {
        if ($workSession->user_id !== $user->id || $workSession->company_id !== $user->company_id) {
            throw ValidationException::withMessages(['timer' => 'The selected work session is not available.']);
        }

        if ($workSession->ended_at !== null) {
            throw ValidationException::withMessages(['timer' => 'This work session has already been stopped.']);
        }

        $endedAt = now();
        $duration = max(1, $workSession->started_at->diffInMinutes($endedAt));

        $workSession->forceFill([
            'ended_at' => $endedAt,
            'duration_minutes' => $duration,
            'notes' => $notes ?? $workSession->notes,
        ])->save();

        return $workSession->refresh();
    }
}
