<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Horario;
use App\Modelo\Personal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio de horarios de trabajo - HU-16 (RF-19).
 *
 * El horario es requisito previo de la evaluacion automatica: sin
 * asignacion el sistema no genera tardanzas ni faltas (CA-HU16-03).
 */
class HorarioService
{
    private const SQLSTATE_TRIGGER = '45000';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * CA-HU16-01: registra un horario con entrada, salida y tolerancia.
     *
     * @param  array<string, mixed>  $datos
     */
    public function registrar(array $datos): Horario
    {
        return $this->enTransaccion(function () use ($datos): Horario {
            $horario = Horario::create($datos + ['activo' => true]);

            $this->auditoria->registrar(
                entidad: 'horario',
                registroId: (int) $horario->horario_id,
                accion: 'REGISTRAR_HORARIO',
                detalle: "Alta del horario {$horario->etiqueta}, tolerancia {$horario->tolerancia_min} min"
            );

            return $horario;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(Horario $horario, array $datos): Horario
    {
        return $this->enTransaccion(function () use ($horario, $datos): Horario {
            $horario->fill($datos);
            $cambios = array_keys($horario->getDirty());
            $horario->save();

            $this->auditoria->registrar(
                entidad: 'horario',
                registroId: (int) $horario->horario_id,
                accion: 'EDITAR_HORARIO',
                detalle: $cambios === []
                    ? "Edicion sin cambios del horario {$horario->nombre}"
                    : "Actualizacion del horario {$horario->nombre}. Campos: ".implode(', ', $cambios)
            );

            return $horario;
        });
    }

    /**
     * Desactiva un horario del catalogo.
     *
     * No se borra: la clave foranea de personal usa ON DELETE SET NULL, de
     * modo que un borrado fisico dejaria sin horario a los trabajadores sin
     * dejar rastro. Se exige que nadie lo tenga asignado.
     */
    public function desactivar(Horario $horario): Horario
    {
        $asignados = $horario->personal()->where('estado', Personal::ESTADO_ACTIVO)->count();

        if ($asignados > 0) {
            throw new ReglaNegocioException(
                "No se puede desactivar: {$asignados} trabajador(es) activo(s) tienen este horario asignado. "
                .'Reasignelos primero a otro horario.'
            );
        }

        return $this->enTransaccion(function () use ($horario): Horario {
            $horario->activo = false;
            $horario->save();

            $this->auditoria->registrar(
                entidad: 'horario',
                registroId: (int) $horario->horario_id,
                accion: 'DESACTIVAR_HORARIO',
                detalle: "Baja del horario {$horario->nombre}"
            );

            return $horario;
        });
    }

    public function reactivar(Horario $horario): Horario
    {
        return $this->enTransaccion(function () use ($horario): Horario {
            $horario->activo = true;
            $horario->save();

            $this->auditoria->registrar(
                entidad: 'horario',
                registroId: (int) $horario->horario_id,
                accion: 'REACTIVAR_HORARIO',
                detalle: "Reactivacion del horario {$horario->nombre}"
            );

            return $horario;
        });
    }

    /**
     * CA-HU16-02: asigna un horario a uno o varios trabajadores.
     *
     * @param  array<int, int|string>  $personalIds
     * @return int Cantidad de trabajadores efectivamente reasignados.
     */
    public function asignar(Horario $horario, array $personalIds): int
    {
        if (! $horario->activo) {
            throw new ReglaNegocioException('No se puede asignar un horario inactivo.');
        }

        $ids = array_map('intval', array_filter($personalIds));

        if ($ids === []) {
            throw new ReglaNegocioException('Seleccione al menos un trabajador.');
        }

        return $this->enTransaccion(function () use ($horario, $ids): int {
            $afectados = Personal::whereIn('personal_id', $ids)
                ->where('estado', Personal::ESTADO_ACTIVO)
                ->update(['horario_id' => $horario->horario_id]);

            $this->auditoria->registrar(
                entidad: 'horario',
                registroId: (int) $horario->horario_id,
                accion: 'ASIGNAR_HORARIO',
                detalle: "Horario {$horario->nombre} asignado a {$afectados} trabajador(es)"
            );

            return $afectados;
        });
    }

    /**
     * Quita el horario a un trabajador. A partir de ese momento el cierre
     * de jornada deja de evaluarlo (CA-HU16-03).
     */
    public function quitarA(Personal $personal): void
    {
        $this->enTransaccion(function () use ($personal): void {
            $nombreHorario = $personal->horario?->nombre ?? 'ninguno';
            $personal->horario_id = null;
            $personal->save();

            $this->auditoria->registrar(
                entidad: 'personal',
                registroId: (int) $personal->personal_id,
                accion: 'QUITAR_HORARIO',
                detalle: "Se retiro el horario {$nombreHorario} al DNI {$personal->dni}"
            );
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
            if ((string) $e->getCode() === self::SQLSTATE_TRIGGER) {
                throw new ReglaNegocioException($e->errorInfo[2] ?? 'La base de datos rechazo la operacion.', 0, $e);
            }

            if ((string) $e->getCode() === '23000') {
                throw new ReglaNegocioException('Ya existe un horario con ese nombre.', 0, $e);
            }

            throw $e;
        }
    }
}
