<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(
    'djen:sync-publications'
)
    ->dailyAt('05:00')
    ->timezone(
        (string) config(
            'services.djen.timezone',
            'America/Sao_Paulo',
        )
    )
    ->withoutOverlapping();
