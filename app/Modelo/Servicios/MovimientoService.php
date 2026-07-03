<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Area;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use App\Modelo\TipoMovimiento;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio de movimientos institucionales - Sprint 3.
 *
 * Concentra las reglas de HU-08 (registro), HU-10 (cambio de estado) y el
 * efecto colateral de la rotacion aprobada sobre el padron, que exige el
 * diagrama DS-HU10.
 *
 * Toda escritura corre en transaccion (RNF-08, RNF-14) y queda auditada
 * con el usuario ejecutor (RNF-10 y CA-HU10-02).
 */
class MovimientoService
{
    /** Codigo SQLSTATE que emiten los disparadores de la base con SIGNAL. */
    private const SQLSTATE_TRIGGER = '45000';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    // -----------------------------------------------------------------
    //  HU-08 - Registro del movimiento
    // -----------------------------------------------------------------

    /**
     * Registra un movimiento nuevo, siempre en estado PENDIENTE.
     *
     * Reglas aplicadas:
     *   - el personal debe estar ACTIVO en el padron (CA-HU08-02);
     *   - la fecha de fin no puede ser anterior a la de inicio (CA-HU08-03);
     *   - los tipos que lo exigen deben traer establecimiento de destino;
     *   - no puede cruzarse con otro movimiento vigente del mismo
     *     trabajador (disparador tg_movimiento_solapa_ins).
     *
     * @param  array<string, mixed>  $datos
     */
    public function registrar(array $datos): MovimientoInstitucional
    {
        $personal = Personal::findOrFail($datos['personal_id']);
        $tipo = TipoMovimiento::findOrFail($datos['tipo_movimiento_id']);

        $this->verificarPersonalActivo($personal);
        $this->verificarDestino($tipo, $datos['establecimiento_destino_id'] ?? null);
        $this->verificarSolapamiento(
            (int) $personal->personal_id,
            (string) $datos['fecha_inicio'],
            (string) $datos['fecha_fin']
        );

        return $this->enTransaccion(function () use ($datos, $personal, $tipo): MovimientoInstitucional {
            $movimiento = MovimientoInstitucional::create([
                'personal_id' => $personal->personal_id,
                'tipo_movimiento_id' => $tipo->tipo_movimiento_id,
                'establecimiento_destino_id' => $tipo->requiere_destino
                    ? $datos['establecimiento_destino_id']
                    : null,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'motivo' => $datos['motivo'],
                'estado' => MovimientoInstitucional::PENDIENTE,
                'motivo_rechazo' => null,
            ]);

            $this->auditoria->registrar(
                entidad: 'movimiento_institucional',
                registroId: (int) $movimiento->movimiento_id,
                accion: 'REGISTRAR_MOVIMIENTO',
                detalle: sprintf(
                    '%s del DNI %s, %s. Estado inicial: PENDIENTE',
                    $tipo->nombre,
                    $personal->dni,
                    $movimiento->periodo
                )
            );

            return $movimiento;
        });
    }

    /**
     * Edita un movimiento que aun no fue resuelto.
     *
     * Solo se admite mientras esta PENDIENTE: una vez aprobado o rechazado
     * el movimiento es un acto administrativo y cambiarlo dejaria sin
     * sustento la decision ya tomada.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(MovimientoInstitucional $movimiento, array $datos): MovimientoInstitucional
    {
        if (! $movimiento->es_editable) {
            throw new ReglaNegocioException(
                "Solo se pueden editar los movimientos PENDIENTES. Este esta {$movimiento->estado}."
            );
        }

        $personal = Personal::findOrFail($datos['personal_id']);
        $tipo = TipoMovimiento::findOrFail($datos['tipo_movimiento_id']);

        $this->verificarPersonalActivo($personal);
        $this->verificarDestino($tipo, $datos['establecimiento_destino_id'] ?? null);
        $this->verificarSolapamiento(
            (int) $personal->personal_id,
            (string) $datos['fecha_inicio'],
            (string) $datos['fecha_fin'],
            (int) $movimiento->movimiento_id
        );

        return $this->enTransaccion(function () use ($movimiento, $datos, $tipo): MovimientoInstitucional {
            $movimiento->fill([
                'personal_id' => $datos['personal_id'],
                'tipo_movimiento_id' => $tipo->tipo_movimiento_id,
                'establecimiento_destino_id' => $tipo->requiere_destino
                    ? $datos['establecimiento_destino_id']
                    : null,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'motivo' => $datos['motivo'],
            ]);

            $cambios = array_keys($movimiento->getDirty());
            $movimiento->save();

            $this->auditoria->registrar(
                entidad: 'movimiento_institucional',
                registroId: (int) $movimiento->movimiento_id,
                accion: 'EDITAR_MOVIMIENTO',
                detalle: $cambios === []
                    ? 'Edicion sin cambios efectivos'
                    : 'Actualizacion del movimiento. Campos: '.implode(', ', $cambios)
            );

            return $movimiento;
        });
    }

    // -----------------------------------------------------------------
    //  HU-10 - Actualizacion del estado
    // -----------------------------------------------------------------

    /**
     * Cambia el estado del movimiento respetando la maquina de estados.
     *
     * @param  string  $nuevoEstado  APROBADO, RECHAZADO o FINALIZADO.
     * @param  string|null  $motivoRechazo  Obligatorio si el estado es RECHAZADO.
     * @param  int|null  $areaDestinoId  Area a la que rota el trabajador,
     *                                   obligatoria al aprobar una ROTACION.
     */
    public function cambiarEstado(
        MovimientoInstitucional $movimiento,
        string $nuevoEstado,
        ?string $motivoRechazo = null,
        ?int $areaDestinoId = null
    ): MovimientoInstitucional {
        $anterior = (string) $movimiento->estado;

        if (! $movimiento->puedeTransicionarA($nuevoEstado)) {
            $posibles = $movimiento->transicionesPosibles();

            throw new ReglaNegocioException(
                "Transicion no permitida: {$anterior} -> {$nuevoEstado}. "
                .($posibles === []
                    ? "Un movimiento {$anterior} ya cerro su ciclo y no admite mas cambios."
                    : 'Desde '.$anterior.' solo puede pasar a: '.implode(' o ', $posibles).'.')
            );
        }

        // ck_movimiento_rechazo: rechazar exige dejar constancia del motivo.
        if ($nuevoEstado === MovimientoInstitucional::RECHAZADO && trim((string) $motivoRechazo) === '') {
            throw new ReglaNegocioException('Para rechazar el movimiento debe indicar el motivo del rechazo.');
        }

        $rotacion = $this->esRotacionConDestino($movimiento);

        if ($nuevoEstado === MovimientoInstitucional::APROBADO && $rotacion) {
            $areaDestinoId = $this->verificarAreaDestino($movimiento, $areaDestinoId);
        }

        return $this->enTransaccion(function () use ($movimiento, $nuevoEstado, $motivoRechazo, $anterior, $areaDestinoId, $rotacion): MovimientoInstitucional {
            $movimiento->estado = $nuevoEstado;

            if ($nuevoEstado === MovimientoInstitucional::RECHAZADO) {
                $movimiento->motivo_rechazo = trim((string) $motivoRechazo);
            }

            $movimiento->save();

            // CA-HU10-02: queda constancia de quien cambio el estado y cuando.
            $this->auditoria->registrar(
                entidad: 'movimiento_institucional',
                registroId: (int) $movimiento->movimiento_id,
                accion: 'CAMBIAR_ESTADO_MOVIMIENTO',
                detalle: sprintf(
                    'Estado %s -> %s del DNI %s%s',
                    $anterior,
                    $nuevoEstado,
                    $movimiento->personal?->dni,
                    $nuevoEstado === MovimientoInstitucional::RECHAZADO
                        ? '. Motivo: '.trim((string) $motivoRechazo)
                        : ''
                )
            );

            // DS-HU10: efecto colateral de la rotacion aprobada sobre el padron.
            if ($nuevoEstado === MovimientoInstitucional::APROBADO && $rotacion && $areaDestinoId !== null) {
                $this->aplicarRotacion($movimiento, $areaDestinoId);
            }

            return $movimiento;
        });
    }

    /**
     * Traslada al trabajador al area de destino. Es permanente: la rotacion
     * es un cambio definitivo de establecimiento o area, a diferencia de la
     * comision de servicio, que es temporal.
     */
    private function aplicarRotacion(MovimientoInstitucional $movimiento, int $areaDestinoId): void
    {
        $personal = $movimiento->personal;

        if ($personal === null) {
            return;
        }

        $areaAnterior = $personal->area?->nombre ?? 'sin area';
        $areaNueva = Area::with('establecimiento')->findOrFail($areaDestinoId);

        $personal->area_id = $areaNueva->area_id;
        $personal->save();

        $this->auditoria->registrar(
            entidad: 'personal',
            registroId: (int) $personal->personal_id,
            accion: 'ROTACION_APLICADA',
            detalle: sprintf(
                'Rotacion aprobada (movimiento %d): %s -> %s (%s)',
                $movimiento->movimiento_id,
                $areaAnterior,
                $areaNueva->nombre,
                $areaNueva->establecimiento?->nombre
            )
        );
    }

    /**
     * Cierra los movimientos aprobados cuyo periodo ya termino.
     *
     * Cubre el tramo final del ciclo de vida que describe la Figura 3.6:
     * "hasta el cierre como Finalizado al concluir el periodo". Se ofrece
     * como accion masiva para que el administrador no tenga que cerrarlos
     * uno por uno.
     *
     * @return int Cantidad de movimientos finalizados.
     */
    public function finalizarVencidos(): int
    {
        $vencidos = MovimientoInstitucional::query()
            ->where('estado', MovimientoInstitucional::APROBADO)
            ->whereDate('fecha_fin', '<', Carbon::today()->toDateString())
            ->with('personal')
            ->get();

        $total = 0;

        foreach ($vencidos as $movimiento) {
            $this->cambiarEstado($movimiento, MovimientoInstitucional::FINALIZADO);
            $total++;
        }

        return $total;
    }

    // -----------------------------------------------------------------
    //  Verificaciones de negocio
    // -----------------------------------------------------------------

    private function verificarPersonalActivo(Personal $personal): void
    {
        if (! $personal->es_activo) {
            throw new ReglaNegocioException(
                "{$personal->nombre_completo} esta INACTIVO en el padron y no admite movimientos."
            );
        }
    }

    /** Comisiones y rotaciones exigen establecimiento de destino. */
    private function verificarDestino(TipoMovimiento $tipo, int|string|null $destinoId): void
    {
        if ($tipo->requiere_destino && empty($destinoId)) {
            throw new ReglaNegocioException(
                "El tipo {$tipo->nombre} exige indicar el establecimiento de destino."
            );
        }
    }

    /**
     * Replica el disparador tg_movimiento_solapa_ins para dar un mensaje
     * util antes de que MySQL rechace la fila.
     */
    private function verificarSolapamiento(
        int $personalId,
        string $inicio,
        string $fin,
        ?int $excluirId = null
    ): void {
        $conflicto = MovimientoInstitucional::query()
            ->where('personal_id', $personalId)
            ->vigentes()
            ->when($excluirId, fn ($q) => $q->where('movimiento_id', '<>', $excluirId))
            ->whereDate('fecha_inicio', '<=', $fin)
            ->whereDate('fecha_fin', '>=', $inicio)
            ->with('tipo')
            ->first();

        if ($conflicto !== null) {
            throw new ReglaNegocioException(sprintf(
                'El trabajador ya tiene un movimiento vigente en ese periodo: %s del %s (estado %s). '
                .'Finalice o rechace ese movimiento antes de registrar uno nuevo.',
                $conflicto->tipo?->nombre ?? 'movimiento',
                $conflicto->periodo,
                $conflicto->estado
            ));
        }
    }

    private function esRotacionConDestino(MovimientoInstitucional $movimiento): bool
    {
        return $movimiento->tipo?->nombre === MovimientoInstitucional::TIPO_ROTACION
            && $movimiento->establecimiento_destino_id !== null;
    }

    /**
     * El area de destino debe pertenecer al establecimiento de destino del
     * movimiento; de lo contrario la rotacion trasladaria al trabajador a
     * un sitio distinto del aprobado.
     */
    private function verificarAreaDestino(MovimientoInstitucional $movimiento, ?int $areaDestinoId): int
    {
        if ($areaDestinoId === null) {
            throw new ReglaNegocioException(
                'Al aprobar una ROTACION debe indicar el area de destino dentro del establecimiento '
                .($movimiento->establecimientoDestino?->nombre ?? 'seleccionado').'.'
            );
        }

        $area = Area::find($areaDestinoId);

        if ($area === null
            || (int) $area->establecimiento_id !== (int) $movimiento->establecimiento_destino_id) {
            throw new ReglaNegocioException(
                'El area de destino no pertenece al establecimiento de destino del movimiento.'
            );
        }

        return (int) $area->area_id;
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

            throw $e;
        }
    }
}
