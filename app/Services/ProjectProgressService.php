<?php

namespace App\Services;

use App\Models\Project;

class ProjectProgressService
{
    public function calculate(Project $project): int
    {
        $total = $project->tasks()->count();

        if ($total === 0) {
            return (int) ($project->progress ?? 0);
        }

        return (int) round(($project->tasks()->where('status', 'completed')->count() / $total) * 100);
    }

    public function sync(Project $project): int
    {
        $progress = $this->calculate($project);

        if ((int) $project->progress !== $progress) {
            $project->forceFill(['progress' => $progress])->save();
        }

        return $progress;
    }
}
