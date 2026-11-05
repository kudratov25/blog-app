<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Batch flush cached article & project read counts to the database every 5 minutes
Schedule::command('views:flush')->everyFiveMinutes()->withoutOverlapping();
