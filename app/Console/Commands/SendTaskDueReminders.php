<?php

namespace App\Console\Commands;

use App\Models\CompanySetting;
use App\Models\Task;
use App\Notifications\TaskDueReminderNotification;
use App\Services\TaskWorkflowService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaskDueReminders extends Command
{
    protected $signature = 'notifications:task-due-reminders {--date=}';

    protected $description = 'Send database reminders for active tasks due today or tomorrow.';

    public function handle(): int
    {
        $runDate = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : today()->toDateString();
        $targetDates = [Carbon::parse($runDate)->toDateString(), Carbon::parse($runDate)->addDay()->toDateString()];
        $sent = 0;

        Task::with(['assignee', 'company.setting'])
            ->whereNotNull('assignee_id')
            ->whereNotNull('due_date')
            ->where(function ($query) use ($targetDates): void {
                foreach ($targetDates as $targetDate) {
                    $query->orWhereDate('due_date', $targetDate);
                }
            })
            ->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES)
            ->chunkById(100, function ($tasks) use ($runDate, &$sent): void {
                foreach ($tasks as $task) {
                    $assignee = $task->assignee;

                    if (! $assignee || $assignee->status !== 'active') {
                        continue;
                    }

                    $setting = $task->company?->setting ?: CompanySetting::firstOrCreate(
                        ['company_id' => $task->company_id],
                        ['timezone' => $task->company?->timezone ?? 'UTC', 'currency' => 'USD', 'settings' => []]
                    );

                    if (! (bool) ($setting->settings['task_due_reminder'] ?? false)) {
                        continue;
                    }

                    $alreadySent = $assignee->notifications()
                        ->where('type', TaskDueReminderNotification::class)
                        ->where('data->task_id', $task->id)
                        ->where('data->reminder_date', $runDate)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $assignee->notify(new TaskDueReminderNotification($task, $runDate));
                    $sent++;
                }
            });

        $this->info("Task due reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
