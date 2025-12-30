<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync Google Calendar events every 10 minutes
Schedule::command('calendars:sync')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
