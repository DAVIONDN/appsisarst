<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Servicio de autenticacion - RF-13 (diagrama DS-RF13).
 *
 * El Sprint 1 implemento el acceso basico. Este sprint completa el flujo
 * que el diseno describe:
 *
 *   - verifica el hash de la contrasena (RNF-02);
 *   - incrementa los intentos fallidos;
 *   - bloquea la cuenta al quinto intento;
 *   - registra cada evento en auditoria (RNF-10);
 *   - entrega la sesion con los roles y permisos del usuario.
 *
 * Nota de seguridad: los mensajes de error nunca distinguen "usuario
 * inexistente" de "clave incorrecta". Hacerlo permitiria descubrir que
 * nombres de usuario existen, igual que en la recuperacion (CA-HU17-03).
 */
class AutenticacionService
{
    /** Resultado del intento de acceso. */
    public const OK = 'OK';

    public const CREDENCIALES_INVALIDAS = 'CREDENCIALES_INVALIDAS';

    public const CUENTA_BLOQUEADA = 'CUENTA_BLOQUEADA';

    public const CUENTA_INACTIVA = 'CUENTA_INACTIVA';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * Intenta abrir la sesion.
     *
     * @return array{resultado: string, usuario: Usuario|null, intentos_restantes: int}
     */
    public function intentar(string $username, string $clave): array
    {
        $usuario = Usuario::where('username', $username)->first();

        if ($usuario === null) {
            // No existe: se responde igual que ante una clave incorrecta.
            return $this->resultado(self::CREDENCIALES_INVALIDAS, null);
        }

        if ($usuario->estaBloqueado()) {
            $this->auditar($usuario, 'ACCESO_BLOQUEADO', 'Intento de acceso a una cuenta BLOQUEADA');

            return $this->resultado(self::CUENTA_BLOQUEADA, $usuario);
        }

        if (! Hash::check($clave, $usuario->getAuthPassword())) {
            $this->registrarFallo($usuario);

            return $this->resultado(
                $usuario->fresh()->estaBloqueado() ? self::CUENTA_BLOQUEADA : self::CREDENCIALES_INVALIDAS,
                $usuario->fresh()
            );
        }

        if (! $usuario->estaActivo()) {
            $this->auditar($usuario, 'ACCESO_DENEGADO', 'Intento de acceso a una cuenta INACTIVA');

            return $this->resultado(self::CUENTA_INACTIVA, $usuario);
        }

        $this->registrarIngreso($usuario);

        return $this->resultado(self::OK, $usuario);
    }

    /** Cierra la sesion dejando constancia. */
    public function cerrarSesion(?Usuario $usuario): void
    {
        if ($usuario !== null) {
            $this->auditar($usuario, 'CERRAR_SESION', "Salida del sistema del usuario {$usuario->username}");
        }
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /**
     * DS-RF13: incrementa el contador y bloquea al llegar al maximo.
     *
     * La restriccion ck_usuario_intentos admite hasta 10, asi que el
     * contador se detiene en el maximo configurado para no violarla.
     */
    private function registrarFallo(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario): void {
            $intentos = min((int) $usuario->intentos_fallidos + 1, Usuario::MAX_INTENTOS);
            $bloquear = $intentos >= Usuario::MAX_INTENTOS;

            $usuario->forceFill([
                'intentos_fallidos' => $intentos,
                'estado' => $bloquear ? Usuario::ESTADO_BLOQUEADO : $usuario->estado,
            ])->save();

            $this->auditar(
                $usuario,
                $bloquear ? 'BLOQUEAR_CUENTA' : 'ACCESO_FALLIDO',
                $bloquear
                    ? "Cuenta {$usuario->username} BLOQUEADA tras {$intentos} intentos fallidos"
                    : "Intento fallido {$intentos} de ".Usuario::MAX_INTENTOS." para {$usuario->username}"
            );
        });
    }

    private function registrarIngreso(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario): void {
            $usuario->forceFill([
                'ultimo_acceso' => now(),
                'intentos_fallidos' => 0,
            ])->save();

            $this->auditar($usuario, 'INICIAR_SESION', "Ingreso al sistema del usuario {$usuario->username}");
        });
    }

    /**
     * La auditoria asocia la entrada al usuario que ejecuta la accion.
     * En los eventos de acceso todavia no hay sesion, asi que se fuerza
     * el identificador de la cuenta afectada.
     */
    private function auditar(Usuario $usuario, string $accion, string $detalle): void
    {
        $this->auditoria->registrarComo(
            usuarioId: (int) $usuario->usuario_id,
            entidad: 'usuario',
            registroId: (int) $usuario->usuario_id,
            accion: $accion,
            detalle: $detalle
        );
    }

    /**
     * @return array{resultado: string, usuario: Usuario|null, intentos_restantes: int}
     */
    private function resultado(string $resultado, ?Usuario $usuario): array
    {
        return [
            'resultado' => $resultado,
            'usuario' => $usuario,
            'intentos_restantes' => $usuario?->intentos_restantes ?? Usuario::MAX_INTENTOS,
        ];
    }

    /** Abre la sesion de Laravel para el usuario ya verificado. */
    public function abrirSesion(Usuario $usuario): void
    {
        Auth::login($usuario);
    }
}
