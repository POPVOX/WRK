<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Primary meeting sync: run all day and dispatch to the default queue.
Schedule::command('calendars:sync')
    ->everyTenMinutes()
    ->withoutOverlapping(9)
    ->onOneServer()
    ->runInBackground()
    ->description('Continuous calendar sync');

// Backstop sync: runs inline daily so meetings still refresh if queue workers hiccup.
Schedule::command('calendars:sync --sync')
    ->dailyAt('05:30')
    ->withoutOverlapping(120)
    ->onOneServer()
    ->description('Daily inline calendar backstop sync');

// Primary Gmail sync: keep outreach signals fresh in the workspace.
Schedule::command('gmail:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping(12)
    ->onOneServer()
    ->runInBackground()
    ->description('Continuous Gmail metadata sync');

// Backstop Gmail sync: inline daily catch-up.
Schedule::command('gmail:sync --sync --days=90 --max=500')
    ->dailyAt('05:45')
    ->withoutOverlapping(120)
    ->onOneServer()
    ->description('Daily inline Gmail backstop sync');
