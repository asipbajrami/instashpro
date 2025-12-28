<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run full pipeline for profiles due for scraping (checks every minute to match exact scheduled times)
Schedule::command('scrape:run-due')->everyMinute();
