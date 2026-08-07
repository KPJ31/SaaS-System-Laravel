<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Task</th><th>Project</th><th>Priority</th><th>Status</th><th>Progress</th><th>Due</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
            @forelse($tasks as $task)
                <tr>
                    <td><strong>{{ $task->title }}</strong><small>{{ ucfirst($task->task_type ?? 'task') }}</small></td>
                    <td>{{ $task->project?->name ?? '-' }}</td>
                    <td>@include('partials.priority-badge', ['priority' => $task->priority])</td>
                    <td>@include('partials.status-badge', ['status' => $task->status]) @if($task->isOverdue())<span class="status-badge status-danger ms-1"><span></span>Overdue</span>@endif</td>
                    <td style="min-width: 120px;"><div class="progress" role="progressbar" aria-valuenow="{{ (int) $task->progress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div></div><small>{{ (int) $task->progress }}%</small></td>
                    <td>{{ $task->due_date?->format('M d, Y') ?? '-' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('employee.tasks.show', $task) }}"><i class="fa-solid fa-eye"></i>View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No tasks found', 'message' => 'Assigned tasks will appear here.'])</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
