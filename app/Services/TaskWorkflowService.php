<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

class TaskWorkflowService
{
    public const STATUSES = ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review', 'completed', 'cancelled'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const TYPES = ['task', 'bug', 'issue', 'improvement'];

    public const TERMINAL_STATUSES = ['completed', 'cancelled'];

    public const GROUPS = [
        'todo' => ['todo', 'assigned'],
        'in_progress' => ['in_progress', 'paused', 'blocked'],
        'review' => ['submitted', 'under_review'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

    public const TRANSITIONS = [
        'todo' => ['assigned', 'in_progress', 'cancelled'],
        'assigned' => ['in_progress', 'cancelled'],
        'in_progress' => ['paused', 'blocked', 'submitted', 'cancelled'],
        'paused' => ['in_progress', 'cancelled'],
        'blocked' => ['in_progress', 'cancelled'],
        'submitted' => ['under_review'],
        'under_review' => ['completed', 'in_progress', 'cancelled'],
    ];

    public function canTransition(Task $task, string $status, User $user): bool
    {
        if (! in_array($status, self::STATUSES, true) || $status === $task->status) {
            return false;
        }

        if (! in_array($status, self::TRANSITIONS[$task->status] ?? [], true)) {
            return false;
        }

        if ($user->role === 'company_admin') {
            return true;
        }

        if ($user->role !== 'employee' || (int) $task->assignee_id !== (int) $user->id) {
            return false;
        }

        return in_array($status, $this->employeeTransitions($task->status), true);
    }

    public function availableTransitions(Task $task, User $user): array
    {
        return array_values(array_filter(
            self::STATUSES,
            fn (string $status): bool => $this->canTransition($task, $status, $user)
        ));
    }

    public function groupFor(string $status): string
    {
        foreach (self::GROUPS as $group => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $group;
            }
        }

        return 'todo';
    }

    public function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    public function isOverdue(Task $task): bool
    {
        return $task->due_date !== null
            && $task->due_date->isPast()
            && ! $this->isTerminal($task->status);
    }

    public function label(string $status): string
    {
        return str_replace('_', ' ', ucfirst($status));
    }

    private function employeeTransitions(string $status): array
    {
        return match ($status) {
            'todo', 'assigned' => ['in_progress'],
            'in_progress' => ['paused', 'blocked', 'submitted'],
            'paused', 'blocked', 'under_review' => ['in_progress'],
            default => [],
        };
    }
}
