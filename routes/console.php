<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orcamentos:expirar')->dailyAt('00:05')->withoutOverlapping();
Schedule::command('backup:diario')->dailyAt('23:50')->withoutOverlapping();
