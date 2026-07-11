<?php

declare(strict_types=1);

use App\Controlador\AsistenciaController;
use App\Controlador\Auth\LoginController;
use App\Controlador\DashboardController;
use App\Controlador\HorarioController;
use App\Controlador\MovimientoController;
use App\Controlador\PersonalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SISARST - Rutas web (Sprint 1: Padron de Personal)
|--------------------------------------------------------------------------
|
| El enrutamiento es la puerta de entrada de la capa Controlador del MVC.
| Cada ruta protegida declara el permiso exacto que exige, resuelto por el
| middleware "permiso" contra las tablas rol / permiso / rol_permiso.
|
|  HU-01  POST   /personal                         PADRON.ESCRIBIR
|  HU-02  PUT    /personal/{personal}              PADRON.EDITAR
|  HU-03  GET    /personal                         PADRON.LEER
|  HU-04  PATCH  /personal/{personal}/baja         PADRON.ELIMINAR
|  HU-18  GET    /personal/{personal}              PADRON.LEER
|
|  HU-08  POST   /movimiento                       MOVIMIENTOS.ESCRIBIR
|  HU-09  GET    /movimiento                       MOVIMIENTOS.LEER
|  HU-09  GET    /movimiento/{movimiento}          MOVIMIENTOS.LEER
|  HU-10  PATCH  /movimiento/{movimiento}/estado   MOVIMIENTOS.EDITAR
|
*/

// ---------------------------------------------------------------------
//  Acceso publico (RF-13)
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------------------
//  Area autenticada
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function (): void {

    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // -----------------------------------------------------------------
    //  Modulo 1: Padron de Personal
    // -----------------------------------------------------------------
    Route::prefix('personal')->name('personal.')->group(function (): void {

        // HU-03: consulta del padron con filtros
        Route::get('/', [PersonalController::class, 'index'])
            ->middleware('permiso:PADRON,LEER')
            ->name('index');

        // HU-01: registro de personal
        Route::get('/nuevo', [PersonalController::class, 'create'])
            ->middleware('permiso:PADRON,ESCRIBIR')
            ->name('create');

        Route::post('/', [PersonalController::class, 'store'])
            ->middleware('permiso:PADRON,ESCRIBIR')
            ->name('store');

        // HU-18: historial laboral completo
        Route::get('/{personal}', [PersonalController::class, 'show'])
            ->whereNumber('personal')
            ->middleware('permiso:PADRON,LEER')
            ->name('show');

        // HU-02: edicion de datos
        Route::get('/{personal}/editar', [PersonalController::class, 'edit'])
            ->whereNumber('personal')
            ->middleware('permiso:PADRON,EDITAR')
            ->name('edit');

        Route::put('/{personal}', [PersonalController::class, 'update'])
            ->whereNumber('personal')
            ->middleware('permiso:PADRON,EDITAR')
            ->name('update');

        // HU-04: baja logica y su reversa
        Route::patch('/{personal}/baja', [PersonalController::class, 'desactivar'])
            ->whereNumber('personal')
            ->middleware('permiso:PADRON,ELIMINAR')
            ->name('desactivar');

        Route::patch('/{personal}/alta', [PersonalController::class, 'reactivar'])
            ->whereNumber('personal')
            ->middleware('permiso:PADRON,ELIMINAR')
            ->name('reactivar');
    });

    // -----------------------------------------------------------------
    //  Modulo 2: Control de Asistencia (Sprint 2)
    // -----------------------------------------------------------------
    Route::prefix('asistencia')->name('asistencia.')->group(function (): void {

        // HU-06: consulta por periodo con filtros
        Route::get('/', [AsistenciaController::class, 'index'])
            ->middleware('permiso:ASISTENCIA,LEER')
            ->name('index');

        // HU-05: marcacion de entrada / salida
        Route::get('/marcar', [AsistenciaController::class, 'create'])
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('create');

        Route::post('/', [AsistenciaController::class, 'store'])
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('store');

        // Correccion manual de una jornada registrada
        Route::get('/{asistencia}/editar', [AsistenciaController::class, 'edit'])
            ->whereNumber('asistencia')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('edit');

        Route::put('/{asistencia}', [AsistenciaController::class, 'update'])
            ->whereNumber('asistencia')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('update');
    });

    // -----------------------------------------------------------------
    //  HU-16: catalogo de horarios y asignacion al personal (Sprint 2)
    // -----------------------------------------------------------------
    Route::prefix('horario')->name('horario.')->group(function (): void {

        Route::get('/', [HorarioController::class, 'index'])
            ->middleware('permiso:ASISTENCIA,LEER')
            ->name('index');

        Route::get('/nuevo', [HorarioController::class, 'create'])
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('create');

        Route::post('/', [HorarioController::class, 'store'])
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('store');

        Route::get('/{horario}/editar', [HorarioController::class, 'edit'])
            ->whereNumber('horario')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('edit');

        Route::put('/{horario}', [HorarioController::class, 'update'])
            ->whereNumber('horario')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('update');

        // CA-HU16-02: asignacion masiva de horario a personal
        Route::get('/{horario}/asignar', [HorarioController::class, 'asignarForm'])
            ->whereNumber('horario')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('asignar.form');

        Route::post('/{horario}/asignar', [HorarioController::class, 'asignar'])
            ->whereNumber('horario')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('asignar');

        Route::patch('/{horario}/baja', [HorarioController::class, 'desactivar'])
            ->whereNumber('horario')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('desactivar');

        Route::patch('/{horario}/alta', [HorarioController::class, 'reactivar'])
            ->whereNumber('horario')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('reactivar');

        Route::patch('/quitar/{personal}', [HorarioController::class, 'quitar'])
            ->whereNumber('personal')
            ->middleware('permiso:ASISTENCIA,ESCRIBIR')
            ->name('quitar');
    });

    // -----------------------------------------------------------------
    //  Modulo 3: Movimientos Institucionales (Sprint 3)
    // -----------------------------------------------------------------
    Route::prefix('movimiento')->name('movimiento.')->group(function (): void {

        // HU-09: consulta e historial de movimientos con filtros
        Route::get('/', [MovimientoController::class, 'index'])
            ->middleware('permiso:MOVIMIENTOS,LEER')
            ->name('index');

        // HU-08: registro de un nuevo movimiento
        Route::get('/nuevo', [MovimientoController::class, 'create'])
            ->middleware('permiso:MOVIMIENTOS,ESCRIBIR')
            ->name('create');

        Route::post('/', [MovimientoController::class, 'store'])
            ->middleware('permiso:MOVIMIENTOS,ESCRIBIR')
            ->name('store');

        // HU-10: finalizar en bloque movimientos aprobados con periodo vencido
        Route::patch('/finalizar-vencidos', [MovimientoController::class, 'finalizarVencidos'])
            ->middleware('permiso:MOVIMIENTOS,EDITAR')
            ->name('finalizar-vencidos');

        // HU-09: detalle individual del movimiento
        Route::get('/{movimiento}', [MovimientoController::class, 'show'])
            ->whereNumber('movimiento')
            ->middleware('permiso:MOVIMIENTOS,LEER')
            ->name('show');

        // HU-08: edicion de un movimiento PENDIENTE
        Route::get('/{movimiento}/editar', [MovimientoController::class, 'edit'])
            ->whereNumber('movimiento')
            ->middleware('permiso:MOVIMIENTOS,ESCRIBIR')
            ->name('edit');

        Route::put('/{movimiento}', [MovimientoController::class, 'update'])
            ->whereNumber('movimiento')
            ->middleware('permiso:MOVIMIENTOS,ESCRIBIR')
            ->name('update');

        // HU-10: cambio de estado segun la maquina de estados
        Route::patch('/{movimiento}/estado', [MovimientoController::class, 'cambiarEstado'])
            ->whereNumber('movimiento')
            ->middleware('permiso:MOVIMIENTOS,EDITAR')
            ->name('estado');
    });
});


// -----------------------------------------------------------------
//  Ruta de seguridad: cualquier URL no definida → redirige al
//  dashboard (autenticado) o al login (invitado). Evita que el
//  usuario llegue a una pantalla de error 404 navigando manualmente.
// -----------------------------------------------------------------
use Illuminate\Support\Facades\Auth;

Route::fallback(function () {
    if (Auth::check()) {
        return redirect()->route('dashboard')
            ->with('warning', 'La URL solicitada no existe o no esta disponible en este modulo.');
    }

    return redirect()->route('login')
        ->withErrors(['acceso' => 'Debe iniciar sesion para acceder al sistema.']);
});
