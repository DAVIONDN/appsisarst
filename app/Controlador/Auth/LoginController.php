<?php

declare(strict_types=1);

namespace App\Controlador\Auth;

use App\Controlador\Controller;
use App\Controlador\Validaciones\LoginRequest;
use App\Modelo\Servicios\AutenticacionService;
use App\Modelo\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Acceso al sistema (RF-13, diagrama DS-RF13).
 *
 * El Sprint 1 implemento el acceso basico. Este sprint completa el flujo:
 * conteo de intentos fallidos, bloqueo al quinto, y auditoria de cada
 * evento. La logica vive en AutenticacionService; aqui solo se traduce el
 * resultado a un mensaje y una redireccion.
 */
class LoginController extends Controller
{
    public function __construct(private readonly AutenticacionService $autenticacion)
    {
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $intento = $this->autenticacion->intentar(
            $request->string('username')->toString(),
            $request->string('password')->toString()
        );

        if ($intento['resultado'] !== AutenticacionService::OK) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => $this->mensajeDeError($intento)]);
        }

        /** @var Usuario $usuario */
        $usuario = $intento['usuario'];

        $this->autenticacion->abrirSesion($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        $this->autenticacion->cerrarSesion($usuario);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('exito', 'Sesion cerrada correctamente.');
    }

    /**
     * Traduce el resultado del intento a un mensaje para el usuario.
     *
     * Los mensajes NO distinguen "usuario inexistente" de "clave
     * incorrecta": hacerlo permitiria descubrir que cuentas existen, el
     * mismo criterio que CA-HU17-03 aplica a la recuperacion.
     *
     * Cuando la cuenta ya esta bloqueada si se informa, porque el usuario
     * legitimo necesita saber por que no entra y a quien recurrir.
     *
     * @param  array{resultado: string, usuario: Usuario|null, intentos_restantes: int}  $intento
     */
    private function mensajeDeError(array $intento): string
    {
        return match ($intento['resultado']) {
            AutenticacionService::CUENTA_BLOQUEADA => 'Su cuenta esta BLOQUEADA por intentos fallidos. '
                .'Solicite el desbloqueo al area de sistemas o restablezca su contrasena desde '
                .'"Olvide mi contrasena".',

            AutenticacionService::CUENTA_INACTIVA => 'Su cuenta no se encuentra activa. '
                .'Comuniquese con el area de sistemas.',

            default => $intento['intentos_restantes'] > 0 && $intento['intentos_restantes'] < Usuario::MAX_INTENTOS
                ? 'Las credenciales ingresadas no son correctas. Le quedan '
                    .$intento['intentos_restantes'].' intento(s) antes de que la cuenta se bloquee.'
                : 'Las credenciales ingresadas no son correctas.',
        };
    }
}
