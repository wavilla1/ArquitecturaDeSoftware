<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Las vistas del mercado y del perfil también cierran subastas vencidas al
// cargarse, por lo que este scheduler es un respaldo si se ejecuta
// `php artisan schedule:work` (no es indispensable para la demo local).
Schedule::command('auctions:close')->everyMinute();
