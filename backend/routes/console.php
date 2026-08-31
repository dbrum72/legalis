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

Schedule::command('datajud:sync-folders')
    ->dailyAt((string) config('services.datajud.sync_time', '04:00'))
    ->timezone((string) config('services.datajud.timezone', 'America/Sao_Paulo'))
    ->withoutOverlapping()
    ->onOneServer();
