<?php

declare(strict_types=1);

namespace App\Controlador\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por modulo y accion (RF-12 / RNF-01).
 *
 * Si el usuario no tiene permiso se redirige al dashboard con un
 * mensaje de advertencia, en lugar de mostrar una pantalla 403 cruda.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $modulo, string $accion): Response
    {
        /** @var \App\Modelo\Usuario|null $usuario */
        $usuario = Auth::user();

        // Sin sesion → login
        if ($usuario === null) {
            return redirect()->route('login')
                ->withErrors(['acceso' => 'Debe iniciar sesion para acceder al sistema.']);
        }

        // Cuenta inactiva → logout + login
        if (! $usuario->estaActivo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Su cuenta no se encuentra activa. Contacte al administrador.']);
        }

        // Sin permiso → dashboard con aviso
        if (! $usuario->tienePermiso($modulo, $accion)) {
            return redirect()->route('dashboard')
                ->with('warning', 'No tiene permisos para acceder a esa seccion (' . $modulo . ' / ' . $accion . ').');
        }

        return $next($request);
    }
}
