<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rappels quotidiens (échéances, objets à remettre, photos, conflits) : voir App\Services\RappelsPlanifies.
Schedule::command('notifications:rappels')->dailyAt('07:00');
