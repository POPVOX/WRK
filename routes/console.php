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
