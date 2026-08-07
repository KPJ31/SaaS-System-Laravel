<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskDueReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Task $task, private readonly string $reminderDate)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $prefix = $notifiable->role === 'company_admin' ? 'company-admin' : 'employee';

        return [
            'title' => 'Task due soon',
            'message' => $this->task->title.' is due '.$this->task->due_date?->format('M d, Y').'.',
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'due_date' => $this->task->due_date?->toDateString(),
            'reminder_date' => $this->reminderDate,
            'url' => route($prefix.'.tasks.show', $this->task),
        ];
    }
}
