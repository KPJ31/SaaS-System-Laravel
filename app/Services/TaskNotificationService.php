<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskNotification;

class TaskNotificationService
{
    public function assigned(Task $task): void
    {
        $task->loadMissing(['assignee', 'project']);

        if (! $task->assignee instanceof User) {
            return;
        }

        $task->assignee->notify(new TaskNotification(
            $task,
            'Task assigned',
            $task->title.' in '.($task->project?->name ?? 'a project').($task->due_date ? ' is due '.$task->due_date->format('M d, Y').'.' : '.')
        ));
    }

    public function sentBack(Task $task): void
    {
        $task->loadMissing('assignee');

        if ($task->assignee instanceof User) {
            $task->assignee->notify(new TaskNotification($task, 'Task returned for changes', $task->title.' was sent back to in progress.'));
        }
    }

    public function completed(Task $task): void
    {
        $task->loadMissing('assignee');

        if ($task->assignee instanceof User) {
            $task->assignee->notify(new TaskNotification($task, 'Task completed', $task->title.' has been approved and completed.'));
        }
    }

    public function submitted(Task $task): void
    {
        $admins = User::where('company_id', $task->company_id)
            ->where('role', 'company_admin')
            ->where('status', 'active')
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new TaskNotification($task, 'Task submitted for review', $task->title.' is waiting for review.'));
        }
    }
}
