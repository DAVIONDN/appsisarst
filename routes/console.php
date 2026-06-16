<?php

declare(strict_types=1);

use App\Controlador\Consola\CerrarJornadaCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| SISARST - Tareas programadas (Sprint 2)
|--------------------------------------------------------------------------
|
| HU-07 / RF-07: la falta la genera una tarea programada al cierre de la
| jornada, no una accion del usuario. Es el actor "Sistema" del diagrama
| de casos de uso del Sprint 2.
|
*/

Schedule::command(CerrarJornadaCommand::class)
    ->dailyAt('23:30')
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->description('Cierre de jornada: genera faltas y tardanzas automaticas (HU-07)');
