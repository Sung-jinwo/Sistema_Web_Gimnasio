<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notificaciones:generar')->dailyAt('07:00');
Schedule::command('notificaciones:expirar')->dailyAt('06:00');
Schedule::command('ventas:procesar-vencimientos')->dailyAt('00:05')->withoutOverlapping();
Schedule::command('cajas:marcar-pendientes')->dailyAt('00:00')->withoutOverlapping();
