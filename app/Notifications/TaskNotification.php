<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly string $title,
        private readonly string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $prefix = $notifiable->role === 'company_admin' ? 'company-admin' : 'employee';

        return [
            'title' => $this->title,
            'message' => $this->message,
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'status' => $this->task->status,
            'due_date' => $this->task->due_date?->toDateString(),
            'url' => route($prefix.'.tasks.show', $this->task),
        ];
    }
}
