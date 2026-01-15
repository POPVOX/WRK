<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Morning calendar sync at 6 AM (catches overnight changes before workday)
Schedule::command('calendars:sync')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Morning calendar sync');

// Regular sync every 30 minutes during business hours
Schedule::command('calendars:sync')
    ->everyThirtyMinutes()
    ->between('07:00', '20:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Regular calendar sync');
