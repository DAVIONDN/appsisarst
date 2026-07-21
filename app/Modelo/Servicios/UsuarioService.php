<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Servicio de dominio de cuentas de usuario - HU-11 y HU-12.
 *
 * Concentra las reglas de creacion, edicion, desactivacion y asignacion de
 * roles. Toda escritura corre en transaccion (RNF-08, RNF-14) y queda
 * auditada con el usuario ejecutor (RNF-10 y CA-HU12-04).
 */
class UsuarioService
{
    private const SQLSTATE_TRIGGER = '45000';

    private const SQLSTATE_DUPLICADO = '23000';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    // -----------------------------------------------------------------
    //  HU-11 - Gestion de cuentas
    // -----------------------------------------------------------------

    /**
     * CA-HU11-01: crea una cuenta asociada a un personal del padron.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, int|string>  $rolIds  Roles a asignar de una vez.
     */
    public function crear(array $datos, array $rolIds = []): Usuario
    {
        $personal = Personal::findOrFail($datos['personal_id']);

        $this->verificarPersonalActivo($personal);
        $this->verificarSinCuenta($personal);

        return $this->enTransaccion(function () use ($datos, $personal, $rolIds): Usuario {
            $usuario = Usuario::create([
                'personal_id' => $personal->personal_id,
                'username' => $datos['username'],
                // RNF-02: la clave se guarda con bcrypt, nunca en texto claro.
                'password_hash' => Hash::make($datos['password']),
                'correo_institucional' => $datos['correo_institucional'],
                'estado' => Usuario::ESTADO_ACTIVO,
            ]);

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'CREAR_USUARIO',
                detalle: "Cuenta {$usuario->username} creada para el DNI {$personal->dni}"
            );

            if ($rolIds !== []) {
                $this->asignarRoles($usuario, $rolIds);
            }

            return $usuario;
        });
    }

    /**
     * CA-HU11-02: edita el correo, el personal asociado y el estado.
     *
     * La contrasena NO se toca aqui: tiene su propia operacion, para que
     * un cambio de correo no arrastre por descuido un cambio de clave.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(Usuario $usuario, array $datos): Usuario
    {
        if (isset($datos['personal_id']) && (int) $datos['personal_id'] !== (int) $usuario->personal_id) {
            $personal = Personal::findOrFail($datos['personal_id']);
            $this->verificarPersonalActivo($personal);
            $this->verificarSinCuenta($personal);
        }

        return $this->enTransaccion(function () use ($usuario, $datos): Usuario {
            $usuario->fill([
                'personal_id' => $datos['personal_id'],
                'username' => $datos['username'],
                'correo_institucional' => $datos['correo_institucional'],
            ]);

            $cambios = array_keys($usuario->getDirty());
            $usuario->save();

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'EDITAR_USUARIO',
                detalle: $cambios === []
                    ? "Edicion sin cambios de la cuenta {$usuario->username}"
                    : "Actualizacion de {$usuario->username}. Campos: ".implode(', ', $cambios)
            );

            return $usuario;
        });
    }

    /**
     * CA-HU11-03: desactiva la cuenta sin eliminar su historial.
     *
     * El usuario pierde el acceso, pero sus registros de auditoria y sus
     * roles permanecen: la trazabilidad no se rompe (RNF-10, RNF-12).
     */
    public function desactivar(Usuario $usuario): Usuario
    {
        $this->verificarNoEsUnoMismo($usuario, 'desactivar su propia cuenta');

        if ($usuario->estado === Usuario::ESTADO_INACTIVO) {
            throw new ReglaNegocioException('La cuenta ya se encuentra inactiva.');
        }

        $this->verificarNoEsElUltimoAdmin($usuario);

        return $this->cambiarEstado($usuario, Usuario::ESTADO_INACTIVO, 'DESACTIVAR_USUARIO');
    }

    public function reactivar(Usuario $usuario): Usuario
    {
        if ($usuario->estaActivo()) {
            throw new ReglaNegocioException('La cuenta ya se encuentra activa.');
        }

        return $this->cambiarEstado($usuario, Usuario::ESTADO_ACTIVO, 'REACTIVAR_USUARIO');
    }

    /**
     * Desbloquea una cuenta que se bloqueo por intentos fallidos (DS-RF13)
     * y reinicia el contador.
     */
    public function desbloquear(Usuario $usuario): Usuario
    {
        if (! $usuario->estaBloqueado()) {
            throw new ReglaNegocioException('La cuenta no esta bloqueada.');
        }

        return $this->enTransaccion(function () use ($usuario): Usuario {
            $usuario->forceFill([
                'estado' => Usuario::ESTADO_ACTIVO,
                'intentos_fallidos' => 0,
            ])->save();

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'DESBLOQUEAR_USUARIO',
                detalle: "Cuenta {$usuario->username} desbloqueada y contador de intentos reiniciado"
            );

            return $usuario;
        });
    }

    /**
     * Restablece la contrasena desde la administracion.
     *
     * Es la via para cuando un usuario perdio el acceso y no puede usar la
     * recuperacion por correo (HU-17): por ejemplo si su correo cambio.
     * La clave nueva la escribe el administrador y se guarda con hash.
     */
    public function restablecerClave(Usuario $usuario, string $claveNueva): Usuario
    {
        return $this->enTransaccion(function () use ($usuario, $claveNueva): Usuario {
            $usuario->forceFill([
                'password_hash' => Hash::make($claveNueva),
                'intentos_fallidos' => 0,
                'token_recuperacion' => null,
                'token_expira' => null,
            ])->save();

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'RESTABLECER_CLAVE_ADMIN',
                detalle: "Contrasena de {$usuario->username} restablecida por un administrador"
            );

            return $usuario;
        });
    }

    // -----------------------------------------------------------------
    //  HU-12 - Asignacion de roles
    // -----------------------------------------------------------------

    /**
     * CA-HU12-01: asigna el conjunto de roles del usuario.
     *
     * Se sincroniza: los roles que no vengan en la lista se retiran. La
     * tabla usuario_rol guarda quien hizo la asignacion (columna
     * asignado_por), como exige CA-HU12-04.
     *
     * @param  array<int, int|string>  $rolIds
     */
    public function asignarRoles(Usuario $usuario, array $rolIds): Usuario
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($rolIds))));

        if ($ids === []) {
            throw new ReglaNegocioException(
                'Seleccione al menos un rol. Una cuenta sin rol no puede hacer nada en el sistema.'
            );
        }

        $this->verificarQuedaUnAdmin($usuario, $ids);

        return $this->enTransaccion(function () use ($usuario, $ids): Usuario {
            $antes = $usuario->roles()->pluck('nombre')->sort()->implode(', ') ?: 'ninguno';

            $asignadoPor = Auth::id();
            $usuario->roles()->sync(
                collect($ids)->mapWithKeys(fn (int $id) => [
                    $id => ['asignado_por' => $asignadoPor, 'fecha_asignacion' => now()],
                ])->all()
            );

            $despues = $usuario->roles()->pluck('nombre')->sort()->implode(', ') ?: 'ninguno';

            // CA-HU12-04: el cambio de roles queda registrado.
            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: 'ASIGNAR_ROLES',
                detalle: "Roles de {$usuario->username}: [{$antes}] -> [{$despues}]"
            );

            return $usuario->load('roles.permisos');
        });
    }

    // -----------------------------------------------------------------
    //  Verificaciones de negocio
    // -----------------------------------------------------------------

    private function verificarPersonalActivo(Personal $personal): void
    {
        if (! $personal->es_activo) {
            throw new ReglaNegocioException(
                "{$personal->nombre_completo} esta INACTIVO en el padron y no puede tener cuenta de acceso."
            );
        }
    }

    /** uq_usuario_personal: un trabajador tiene a lo sumo una cuenta. */
    private function verificarSinCuenta(Personal $personal): void
    {
        if ($personal->usuario()->exists()) {
            throw new ReglaNegocioException(
                "{$personal->nombre_completo} ya tiene una cuenta de acceso asociada."
            );
        }
    }

    private function verificarNoEsUnoMismo(Usuario $usuario, string $accion): void
    {
        if ((int) $usuario->usuario_id === (int) Auth::id()) {
            throw new ReglaNegocioException("No puede {$accion}.");
        }
    }

    /**
     * Impide dejar al sistema sin ningun administrador activo.
     *
     * No es un criterio del analisis, pero sin esta guarda un descuido
     * dejaria el sistema sin nadie que pueda administrar cuentas, y la
     * unica salida seria editar la base a mano.
     */
    private function verificarNoEsElUltimoAdmin(Usuario $usuario): void
    {
        if (! $usuario->tieneRol(Rol::ADMIN_SISTEMA)) {
            return;
        }

        $otrosAdmins = Usuario::query()
            ->activos()
            ->where('usuario_id', '<>', $usuario->usuario_id)
            ->whereHas('roles', fn ($q) => $q->where('nombre', Rol::ADMIN_SISTEMA))
            ->count();

        if ($otrosAdmins === 0) {
            throw new ReglaNegocioException(
                'No se puede desactivar la unica cuenta con rol ADMIN_SISTEMA activa. '
                .'Asigne ese rol a otra cuenta antes de continuar.'
            );
        }
    }

    /**
     * @param  array<int, int>  $nuevosRolIds
     */
    private function verificarQuedaUnAdmin(Usuario $usuario, array $nuevosRolIds): void
    {
        if (! $usuario->tieneRol(Rol::ADMIN_SISTEMA)) {
            return;
        }

        $idAdmin = (int) Rol::where('nombre', Rol::ADMIN_SISTEMA)->value('rol_id');

        if (in_array($idAdmin, $nuevosRolIds, true)) {
            return; // conserva el rol
        }

        $otrosAdmins = Usuario::query()
            ->activos()
            ->where('usuario_id', '<>', $usuario->usuario_id)
            ->whereHas('roles', fn ($q) => $q->where('nombre', Rol::ADMIN_SISTEMA))
            ->count();

        if ($otrosAdmins === 0) {
            throw new ReglaNegocioException(
                'No se puede retirar el rol ADMIN_SISTEMA a la unica cuenta que lo tiene. '
                .'Asigneselo antes a otra cuenta activa.'
            );
        }
    }

    private function cambiarEstado(Usuario $usuario, string $estado, string $accion): Usuario
    {
        return $this->enTransaccion(function () use ($usuario, $estado, $accion): Usuario {
            $anterior = (string) $usuario->estado;
            $usuario->forceFill(['estado' => $estado])->save();

            $this->auditoria->registrar(
                entidad: 'usuario',
                registroId: (int) $usuario->usuario_id,
                accion: $accion,
                detalle: "Estado de {$usuario->username}: {$anterior} -> {$estado}"
            );

            return $usuario;
        });
    }

    /**
     * @template T
     *
     * @param  callable():T  $operacion
     * @return T
     */
    private function enTransaccion(callable $operacion): mixed
    {
        try {
            return DB::transaction($operacion);
        } catch (QueryException $e) {
            $codigo = (string) $e->getCode();

            if ($codigo === self::SQLSTATE_TRIGGER) {
                throw new ReglaNegocioException($e->errorInfo[2] ?? 'La base de datos rechazo la operacion.', 0, $e);
            }

            if ($codigo === self::SQLSTATE_DUPLICADO) {
                throw new ReglaNegocioException(
                    'Ya existe una cuenta con ese nombre de usuario o ese correo institucional.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }
}
