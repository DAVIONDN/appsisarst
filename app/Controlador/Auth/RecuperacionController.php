<?php

declare(strict_types=1);

namespace App\Controlador\Auth;

use App\Controlador\Controller;
use App\Controlador\Validaciones\ClaveRequest;
use App\Controlador\Validaciones\SolicitarRecuperacionRequest;
use App\Modelo\Servicios\RecuperacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Recuperacion de contrasena (HU-17 / RF-14).
 *
 * Es el caso de uso que la revision tecnica encontro ausente por completo
 * (observacion H-01). Sigue el diagrama DS-HU17:
 *
 *   CA-HU17-01  solicitar desde la pantalla de acceso
 *   CA-HU17-02  enlace de un solo uso, vigencia 30 minutos
 *   CA-HU17-03  respuesta identica exista o no la cuenta
 *   CA-HU17-04  la clave nueva se guarda con hash y queda auditada
 */
class RecuperacionController extends Controller
{
    /**
     * Mensaje unico de respuesta a la solicitud.
     *
     * CA-HU17-03: se muestra siempre, exista o no la cuenta. Es la pieza
     * central del criterio, por eso esta en una constante y no repetido
     * en varios sitios donde podria divergir.
     */
    private const MENSAJE_GENERICO = 'Si el correo corresponde a una cuenta activa del sistema, '
        .'le enviamos un enlace para restablecer su contrasena. Revise su bandeja de entrada; '
        .'el enlace vence en 30 minutos.';

    public function __construct(private readonly RecuperacionService $recuperacion)
    {
    }

    // -----------------------------------------------------------------
    //  CA-HU17-01 - Solicitud
    // -----------------------------------------------------------------

    public function solicitar(): View
    {
        return view('auth.solicitar-recuperacion');
    }

    public function enviar(SolicitarRecuperacionRequest $request): RedirectResponse
    {
        $this->recuperacion->solicitar($request->string('correo_institucional')->toString());

        // Siempre la misma respuesta, sin importar el resultado real.
        return redirect()
            ->route('login')
            ->with('exito', self::MENSAJE_GENERICO);
    }

    // -----------------------------------------------------------------
    //  CA-HU17-02 - Formulario del enlace
    // -----------------------------------------------------------------

    public function formulario(string $token): View|RedirectResponse
    {
        $usuario = $this->recuperacion->usuarioPorToken($token);

        if ($usuario === null) {
            return redirect()
                ->route('recuperacion.solicitar')
                ->withErrors(['error' => 'El enlace no es valido o ya vencio. Solicite uno nuevo.']);
        }

        return view('auth.restablecer', [
            'token' => $token,
            'usuario' => $usuario,
            'minutosRestantes' => $usuario->minutosParaExpirar(),
        ]);
    }

    // -----------------------------------------------------------------
    //  CA-HU17-04 - Restablecimiento
    // -----------------------------------------------------------------

    /**
     * El token se lee del propio ClaveRequest.
     *
     * No se puede recibir ademas un Illuminate\Http\Request: el contenedor
     * solo resuelve un objeto de peticion por accion, y pedir dos produce
     * un ArgumentCountError en tiempo de ejecucion.
     */
    public function restablecer(ClaveRequest $request): RedirectResponse
    {
        $token = (string) $request->input('token');
        $usuario = $this->recuperacion->usuarioPorToken($token);

        // Se vuelve a verificar el token al guardar: entre que se abrio el
        // formulario y se envio pudo vencer, o haberse usado en otra
        // pestana. Es lo que hace que el enlace sea de un solo uso.
        if ($usuario === null) {
            return redirect()
                ->route('recuperacion.solicitar')
                ->withErrors(['error' => 'El enlace no es valido, ya vencio o ya fue utilizado. Solicite uno nuevo.']);
        }

        $this->recuperacion->restablecer($usuario, $request->string('password')->toString());

        return redirect()
            ->route('login')
            ->with('exito', 'Su contrasena fue restablecida. Ya puede ingresar con la nueva clave.');
    }
}
