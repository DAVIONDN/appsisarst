<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Personal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio del padron de personal - Sprint 1.
 *
 * Concentra las reglas de negocio de HU-01, HU-02 y HU-04 para que el
 * controlador solo coordine peticion y respuesta (capa Controlador del MVC).
 *
 * Cada operacion de escritura corre dentro de una transaccion (RNF-08 y
 * RNF-14) y deja constancia en el log de auditoria (RNF-10).
 */
class PadronService
{
    /** Codigo SQLSTATE que emiten los disparadores de la base con SIGNAL. */
    private const SQLSTATE_TRIGGER = '45000';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * HU-01: registra un trabajador nuevo en el padron.
     *
     * @param  array<string, mixed>  $datos  Datos ya validados por el FormRequest.
     */
    public function registrar(array $datos): Personal
    {
        return $this->enTransaccion(function () use ($datos): Personal {
            $datos['estado'] = Personal::ESTADO_ACTIVO;
            $datos['motivo_baja'] = null;

            $personal = Personal::create($datos);

            $this->auditoria->registrar(
                entidad: 'personal',
                registroId: (int) $personal->personal_id,
                accion: 'REGISTRAR_PERSONAL',
                detalle: "Alta del trabajador con DNI {$personal->dni} ({$personal->nombre_completo})"
            );

            return $personal;
        });
    }

    /**
     * HU-02: actualiza los datos de un trabajador existente.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(Personal $personal, array $datos): Personal
    {
        return $this->enTransaccion(function () use ($personal, $datos): Personal {
            // El estado y el motivo de baja no se tocan desde la edicion:
            // su unico camino es la desactivacion de HU-04.
            unset($datos['estado'], $datos['motivo_baja']);

            $personal->fill($datos);
            $cambios = array_keys($personal->getDirty());
            $personal->save();

            $this->auditoria->registrar(
                entidad: 'personal',
                registroId: (int) $personal->personal_id,
                accion: 'EDITAR_PERSONAL',
                detalle: $cambios === []
                    ? "Edicion sin cambios efectivos del DNI {$personal->dni}"
                    : "Actualizacion del DNI {$personal->dni}. Campos: ".implode(', ', $cambios)
            );

            return $personal;
        });
    }

    /**
     * HU-04: desactivacion logica del trabajador.
     *
     * El registro nunca se borra: cambia a estado INACTIVO y conserva todo
     * su historial (CA-HU04-03 y CA-HU04-04). Antes de proceder se verifica
     * que no arrastre movimientos institucionales vigentes, tal como exige
     * el diagrama de secuencia DS-HU04.
     */
    public function desactivar(Personal $personal, string $motivoBaja): Personal
    {
        if (! $personal->es_activo) {
            throw new ReglaNegocioException('El trabajador ya se encuentra inactivo.');
        }

        $vigentes = $personal->movimientos()->vigentes()->count();

        if ($vigentes > 0) {
            throw new ReglaNegocioException(
                "No se puede desactivar: el trabajador tiene {$vigentes} movimiento(s) "
                .'institucional(es) vigente(s). Finalice o rechace esos movimientos primero.'
            );
        }

        return $this->enTransaccion(function () use ($personal, $motivoBaja): Personal {
            $personal->estado = Personal::ESTADO_INACTIVO;
            $personal->motivo_baja = $motivoBaja;
            $personal->save();

            $this->auditoria->registrar(
                entidad: 'personal',
                registroId: (int) $personal->personal_id,
                accion: 'DESACTIVAR_PERSONAL',
                detalle: "Baja del DNI {$personal->dni}. Motivo: {$motivoBaja}"
            );

            return $personal;
        });
    }

    /**
     * Reactiva a un trabajador dado de baja por error.
     *
     * No corresponde a ninguna HU del sprint, pero el criterio CA-HU04-04
     * exige conservar el registro, y sin reactivacion un error de operacion
     * seria irreversible.
     */
    public function reactivar(Personal $personal): Personal
    {
        if ($personal->es_activo) {
            throw new ReglaNegocioException('El trabajador ya se encuentra activo.');
        }

        return $this->enTransaccion(function () use ($personal): Personal {
            $personal->estado = Personal::ESTADO_ACTIVO;
            $personal->motivo_baja = null;
            $personal->save();

            $this->auditoria->registrar(
                entidad: 'personal',
                registroId: (int) $personal->personal_id,
                accion: 'REACTIVAR_PERSONAL',
                detalle: "Reactivacion del DNI {$personal->dni}"
            );

            return $personal;
        });
    }

    /**
     * Ejecuta la operacion dentro de una transaccion y traduce los errores
     * de los disparadores de MySQL a excepciones de negocio legibles.
     *
     * La base define cinco disparadores que rechazan operaciones invalidas
     * con SIGNAL SQLSTATE '45000' (p. ej. fecha de ingreso futura).
     *
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
            if ((string) $e->getCode() === self::SQLSTATE_TRIGGER) {
                throw new ReglaNegocioException(
                    $e->errorInfo[2] ?? 'La base de datos rechazo la operacion.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }
}
