<?php

declare(strict_types=1);

use App\Controlador\Auth\LoginController;
use App\Controlador\DashboardController;
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
|  HU-01  POST   /personal                    PADRON.ESCRIBIR
|  HU-02  PUT    /personal/{personal}         PADRON.EDITAR
|  HU-03  GET    /personal                    PADRON.LEER
|  HU-04  PATCH  /personal/{personal}/baja    PADRON.ELIMINAR
|  HU-18  GET    /personal/{personal}         PADRON.LEER
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
