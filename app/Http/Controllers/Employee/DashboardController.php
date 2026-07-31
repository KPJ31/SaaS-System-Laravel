<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        return view('employee.dashboard', [
            'tasks' => Task::with('project')->where('company_id', $user->company_id)->where('assignee_id', $user->id)->latest()->take(8)->get(),
            'openTasksCount' => Task::where('company_id', $user->company_id)->where('assignee_id', $user->id)->whereIn('status', ['todo', 'in_progress'])->count(),
            'sessions' => WorkSession::with(['project', 'task'])->where('company_id', $user->company_id)->where('user_id', $user->id)->latest()->take(5)->get(),
        ]);
    }
}
