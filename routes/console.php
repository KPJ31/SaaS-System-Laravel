<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('permission:cache-reset', function () {
    $this->info('Permission cache reset is not required for the built-in Elevanix permission system.');
})->purpose('Reset permission cache');

Schedule::command('notifications:task-due-reminders')->dailyAt('08:00');
