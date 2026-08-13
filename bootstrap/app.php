<?php

use App\Controlador\Consola\CerrarJornadaCommand;
use App\Controlador\Middleware\VerificarPermiso;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        // HU-07: el comando de cierre de jornada vive en Controlador/Consola/,
        // fuera del directorio que Laravel descubre automaticamente.
        CerrarJornadaCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {

        // Confiar en el proxy de Vercel para detectar correctamente HTTPS.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO
        );

        // Alias usado en las rutas: ->middleware('permiso:PADRON,LEER')
        $middleware->alias([
            'permiso' => VerificarPermiso::class,
        ]);

        // Sin sesión activa, devolver a login.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 403 - Sin permisos
        $exceptions->renderable(function (
            \Illuminate\Auth\Access\AuthorizationException $e,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No autorizado'
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with(
                    'warning',
                    'No tiene permisos para acceder a esta seccion.'
                );
        });

        // 404 - Ruta no encontrada
        $exceptions->renderable(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Recurso no encontrado'
                ], 404);
            }

            if (\Illuminate\Support\Facades\Auth::check()) {
                return redirect()
                    ->route('dashboard')
                    ->with(
                        'warning',
                        'La pagina solicitada no existe.'
                    );
            }

            return redirect()->route('login');
        });
    })
    ->create();