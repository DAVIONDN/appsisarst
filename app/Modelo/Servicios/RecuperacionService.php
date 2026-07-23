<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Servicio de recuperacion de contrasena - HU-17 (RF-14).
 *
 * Reglas del criterio de aceptacion:
 *   CA-HU17-01  el usuario solicita desde la pantalla de acceso;
 *   CA-HU17-02  el enlace es de un solo uso y vence a los 30 minutos;
 *   CA-HU17-03  el sistema NUNCA revela si un correo esta registrado;
 *   CA-HU17-04  la nueva clave se guarda con hash y el cambio se audita.
 *
 * Decision de seguridad: en la base se guarda el HASH del token, no el
 * token. Si alguien leyera la tabla usuario no podria construir un enlace
 * valido. Es el mismo criterio con el que se guarda la contrasena (RNF-02).
 */
class RecuperacionService
{
    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * CA-HU17-01: genera y envia el enlace de recuperacion.
     *
     * Devuelve siempre void, sin indicar si el correo existia. Quien llama
     * muestra el mismo mensaje en todos los casos (CA-HU17-03).
     */
    public function solicitar(string $correo): void
    {
        $usuario = Usuario::where('correo_institucional', mb_strtolower(trim($correo)))->first();

        // CA-HU17-03: no se distingue el caso "no existe" del caso "existe".
        // Tampoco se avisa de una cuenta inactiva o bloqueada: eso tambien
        // seria informacion util para alguien que este tanteando correos.
        if ($usuario === null || ! $usuario->estaActivo()) {
            Log::info('Solicitud de recuperacion sobre un correo sin cuenta activa.', [
                'correo' => $correo,
            ]);

            return;
        }

        $token = Str::random(64);

        DB::transaction(function () use ($usuario, $token): void {
            $usuario->forceFill([
                'token_recuperacion' => hash('sha256', $token),
                'token_expira' => now()->addMinutes(Usuario::MINUTOS_TOKEN),
            ])->save();

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'SOLICITAR_RECUPERACION',
                detalle: "Enlace de recuperacion generado para {$usuario->username}, "
                    .'vigencia '.Usuario::MINUTOS_TOKEN.' minutos'
            );
        });

        $this->enviarCorreo($usuario, $token);
    }

    /**
     * Busca la cuenta a la que corresponde un token vigente.
     *
     * Recorre solo las cuentas con token sin vencer, que en la practica
     * son unas pocas, y compara con hash_equals para no filtrar informacion
     * por el tiempo de comparacion.
     */
    public function usuarioPorToken(string $token): ?Usuario
    {
        return Usuario::query()
            ->whereNotNull('token_recuperacion')
            ->where('token_expira', '>', now())
            ->get()
            ->first(fn (Usuario $u): bool => $u->tokenCoincide($token));
    }

    /**
     * CA-HU17-02 y CA-HU17-04: restablece la clave y consume el token.
     *
     * El token se limpia en la misma transaccion, de modo que el enlace
     * queda invalidado apenas se usa. Tambien se reinicia el contador de
     * intentos fallidos y se desbloquea la cuenta si lo estaba: el usuario
     * demostro ser el dueno del correo institucional.
     */
    public function restablecer(Usuario $usuario, string $claveNueva): Usuario
    {
        return DB::transaction(function () use ($usuario, $claveNueva): Usuario {
            $estabaBloqueado = $usuario->estaBloqueado();

            $usuario->forceFill([
                'password_hash' => Hash::make($claveNueva),
                'token_recuperacion' => null,
                'token_expira' => null,
                'intentos_fallidos' => 0,
                'estado' => $estabaBloqueado ? Usuario::ESTADO_ACTIVO : $usuario->estado,
            ])->save();

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'RESTABLECER_CLAVE',
                detalle: "Contrasena de {$usuario->username} restablecida mediante enlace de recuperacion"
                    .($estabaBloqueado ? '. La cuenta estaba BLOQUEADA y se reactivo.' : '')
            );

            return $usuario;
        });
    }

    /** Invalida un enlace vigente sin usarlo. */
    public function invalidarToken(Usuario $usuario): void
    {
        $usuario->forceFill([
            'token_recuperacion' => null,
            'token_expira' => null,
        ])->save();
    }

    // -----------------------------------------------------------------
    //  Envio del correo
    // -----------------------------------------------------------------

    /**
     * En desarrollo MAIL_MAILER=log deja el correo en storage/logs; en
     * pruebas se configura Mailtrap. El servicio no distingue: siempre
     * usa la configuracion de Laravel.
     */
    private function enviarCorreo(Usuario $usuario, string $token): void
    {
        $enlace = route('recuperacion.formulario', ['token' => $token]);

        Mail::send(
            'correos.recuperacion',
            [
                'usuario' => $usuario,
                'enlace' => $enlace,
                'minutos' => Usuario::MINUTOS_TOKEN,
            ],
            function ($mensaje) use ($usuario): void {
                $mensaje->to($usuario->correo_institucional)
                    ->subject('SISARST - Recuperacion de contrasena');
            }
        );
    }
}
