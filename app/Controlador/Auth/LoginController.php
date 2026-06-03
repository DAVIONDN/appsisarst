<?php

declare(strict_types=1);

namespace App\Controlador\Auth;

use App\Controlador\Controller;
use App\Controlador\Validaciones\LoginRequest;
use App\Modelo\Usuario;
use App\Modelo\Servicios\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Acceso al sistema (RF-13).
 *
 * Alcance del Sprint 1: iniciar y cerrar sesion, que es lo minimo que el
 * modulo del padron necesita para poder verificar permisos por rol.
 * El bloqueo por intentos fallidos y la recuperacion de contrasena
 * (HU-17) se implementan en el Sprint 4.
 */
class LoginController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /** Muestra la pantalla de acceso. */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /** Valida las credenciales y abre la sesion. */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credenciales = [
            'username' => $request->string('username')->toString(),
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::attempt($credenciales)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Las credenciales ingresadas no son correctas.']);
        }

        /** @var Usuario $usuario */
        $usuario = Auth::user();

        if (! $usuario->estaActivo()) {
            Auth::logout();

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Su cuenta no se encuentra activa. Comuniquese con el area de sistemas.']);
        }

        $request->session()->regenerate();

        $usuario->forceFill(['ultimo_acceso' => now(), 'intentos_fallidos' => 0])->save();

        $this->auditoria->registrar(
            entidad: 'usuario',
            registroId: (int) $usuario->usuario_id,
            accion: 'INICIAR_SESION',
            detalle: "Ingreso al sistema del usuario {$usuario->username}"
        );

        return redirect()->intended(route('dashboard'));
    }

    /** Cierra la sesion del usuario. */
    public function destroy(Request $request): RedirectResponse
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if ($usuario !== null) {
            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'CERRAR_SESION',
                detalle: "Salida del sistema del usuario {$usuario->username}"
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('exito', 'Sesion cerrada correctamente.');
    }
}
