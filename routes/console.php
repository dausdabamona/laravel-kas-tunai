<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup harian (jalankan scheduler/cron di server: schedule:work atau cron).
Schedule::command('backup:jalankan')->dailyAt('23:30');
