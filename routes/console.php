<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('permission:cache-reset', function () {
    $this->info('Permission cache reset is not required for the built-in Elevanix permission system.');
})->purpose('Reset permission cache');
