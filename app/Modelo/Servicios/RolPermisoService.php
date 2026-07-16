<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Permiso;
use App\Modelo\Rol;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio de roles y permisos - HU-12 (RF-12).
 *
 * Cubre CA-HU12-03: los permisos son configurables por modulo y por tipo
 * de accion (LEER, ESCRIBIR, EDITAR, ELIMINAR). El sistema no inventa
 * permisos: trabaja sobre el catalogo que carga el script de la base.
 */
class RolPermisoService
{
    private const SQLSTATE_DUPLICADO = '23000';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * CA-HU12-03: fija el conjunto de permisos de un rol.
     *
     * Se sincroniza: los permisos que no vengan en la lista se retiran.
     * Es lo que permite "habilitar o restringir las acciones de leer,
     * escribir, editar y eliminar en cada modulo del sistema".
     *
     * @param  array<int, int|string>  $permisoIds
     */
    public function sincronizarPermisos(Rol $rol, array $permisoIds): Rol
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($permisoIds))));

        $this->verificarAdminConservaTodo($rol, $ids);

        return $this->enTransaccion(function () use ($rol, $ids): Rol {
            $antes = $rol->permisos()->count();
            $rol->permisos()->sync($ids);
            $despues = count($ids);

            // CA-HU12-04: el cambio queda registrado con usuario y fecha.
            $this->auditoria->registrar(
                entidad: 'rol',
                registroId: (int) $rol->rol_id,
                accion: 'CONFIGURAR_PERMISOS',
                detalle: sprintf(
                    'Permisos del rol %s: %d -> %d. Detalle: %s',
                    $rol->nombre,
                    $antes,
                    $despues,
                    $this->resumirPermisos($ids)
                )
            );

            return $rol->load('permisos');
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crearRol(array $datos): Rol
    {
        return $this->enTransaccion(function () use ($datos): Rol {
            $rol = Rol::create([
                'nombre' => mb_strtoupper(trim((string) $datos['nombre'])),
                'descripcion' => $datos['descripcion'] ?? null,
                'activo' => true,
            ]);

            $this->auditoria->registrar(
                entidad: 'rol',
                registroId: (int) $rol->rol_id,
                accion: 'CREAR_ROL',
                detalle: "Rol {$rol->nombre} creado"
            );

            return $rol;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizarRol(Rol $rol, array $datos): Rol
    {
        return $this->enTransaccion(function () use ($rol, $datos): Rol {
            $rol->fill(['descripcion' => $datos['descripcion'] ?? null]);

            // El nombre de los cuatro roles del diseno no se cambia: el
            // codigo los referencia por nombre (Rol::ADMIN_SISTEMA, etc.).
            if (! $this->esRolDelDiseno($rol)) {
                $rol->nombre = mb_strtoupper(trim((string) $datos['nombre']));
            }

            $cambios = array_keys($rol->getDirty());
            $rol->save();

            $this->auditoria->registrar(
                entidad: 'rol',
                registroId: (int) $rol->rol_id,
                accion: 'EDITAR_ROL',
                detalle: $cambios === []
                    ? "Edicion sin cambios del rol {$rol->nombre}"
                    : "Actualizacion del rol {$rol->nombre}. Campos: ".implode(', ', $cambios)
            );

            return $rol;
        });
    }

    /**
     * Matriz de permisos agrupada por modulo, para dibujar la pantalla de
     * configuracion como una grilla modulo x accion.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<string, Permiso>>
     */
    public function matriz()
    {
        return Permiso::query()
            ->orderBy('modulo')
            ->orderBy('permiso_id')
            ->get()
            ->groupBy('modulo')
            ->map(fn ($permisos) => $permisos->keyBy('accion'));
    }

    /**
     * @return array<int, string>
     */
    public function acciones(): array
    {
        return [Permiso::LEER, Permiso::ESCRIBIR, Permiso::EDITAR, Permiso::ELIMINAR];
    }

    // -----------------------------------------------------------------
    //  Verificaciones
    // -----------------------------------------------------------------

    /** Los cuatro roles que define el documento de diseno. */
    private function esRolDelDiseno(Rol $rol): bool
    {
        return in_array(
            $rol->nombre,
            [Rol::ADMIN_SISTEMA, Rol::ADMIN_RRHH, Rol::JEFE_AREA, Rol::GERENTE_RED],
            true
        );
    }

    /**
     * ADMIN_SISTEMA debe conservar los permisos del modulo USUARIOS.
     *
     * Sin ellos nadie podria volver a entrar a la pantalla de roles para
     * corregir el error: el sistema quedaria sin administracion posible.
     *
     * @param  array<int, int>  $permisoIds
     */
    private function verificarAdminConservaTodo(Rol $rol, array $permisoIds): void
    {
        if ($rol->nombre !== Rol::ADMIN_SISTEMA) {
            return;
        }

        $idsUsuarios = Permiso::where('modulo', 'USUARIOS')->pluck('permiso_id')->all();
        $faltantes = array_diff($idsUsuarios, $permisoIds);

        if ($faltantes !== []) {
            throw new ReglaNegocioException(
                'El rol ADMIN_SISTEMA debe conservar todos los permisos del modulo USUARIOS. '
                .'Sin ellos nadie podria volver a administrar cuentas ni roles.'
            );
        }
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function resumirPermisos(array $ids): string
    {
        if ($ids === []) {
            return 'sin permisos';
        }

        return Permiso::whereIn('permiso_id', $ids)
            ->orderBy('modulo')
            ->get()
            ->groupBy('modulo')
            ->map(fn ($p, $modulo) => $modulo.'('.$p->pluck('accion')->implode('/').')')
            ->implode(', ');
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
            if ((string) $e->getCode() === self::SQLSTATE_DUPLICADO) {
                throw new ReglaNegocioException('Ya existe un rol con ese nombre.', 0, $e);
            }

            throw $e;
        }
    }
}
