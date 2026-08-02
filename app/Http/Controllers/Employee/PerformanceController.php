<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Task;
use App\Models\WorkSession;
use Carbon\CarbonPeriod;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    use HandlesEmployeeAccess;

    public function __invoke(): View
    {
        $tasks = Task::where('company_id', $this->companyId())->where('assignee_id', auth()->id());
        $total = (clone $tasks)->count();
        $completed = (clone $tasks)->where('status', 'completed')->count();
        $workMinutes = WorkSession::where('company_id', $this->companyId())->where('user_id', auth()->id())->whereNotNull('ended_at')->sum('duration_minutes');
        $estimatedMinutes = (clone $tasks)->whereNotNull('estimated_hours')->sum('estimated_hours') * 60;
        $activeDays = WorkSession::where('company_id', $this->companyId())->where('user_id', auth()->id())->whereNotNull('ended_at')->selectRaw('DATE(started_at) as day')->distinct()->count();

        $completedRate = $total > 0 ? round(($completed / $total) * 100, 1) : null;
        $timeEfficiency = $estimatedMinutes > 0 ? round(min(100, ($estimatedMinutes / max(1, $workMinutes)) * 100), 1) : null;
        $consistency = min(100, round(($activeDays / max(1, now()->day)) * 100, 1));
        $score = $completedRate === null ? null : round(($completedRate * 0.4) + (($timeEfficiency ?? 100) * 0.3) + ($consistency * 0.3));
        $label = match (true) {
            $score === null => 'Not enough data',
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Average',
            default => 'Needs Improvement',
        };
        $labels = [];
        $values = [];
        foreach (CarbonPeriod::create(now()->subMonths(5)->startOfMonth(), '1 month', now()->startOfMonth()) as $month) {
            $labels[] = $month->format('M');
            $values[] = Task::where('company_id', $this->companyId())->where('assignee_id', auth()->id())->where('status', 'completed')->whereMonth('completed_at', $month->month)->whereYear('completed_at', $month->year)->count();
        }

        return view('employee.performance.index', [
            'score' => $score,
            'label' => $label,
            'completedRate' => $completedRate,
            'timeEfficiency' => $timeEfficiency,
            'consistency' => $consistency,
            'completed' => $completed,
            'workHours' => round($workMinutes / 60, 2),
            'recentCompleted' => (clone $tasks)->with('project')->where('status', 'completed')->latest('completed_at')->take(8)->get(),
            'chartData' => ['labels' => $labels, 'values' => $values],
        ]);
    }
}
