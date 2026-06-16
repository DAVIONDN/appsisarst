<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Asistencia;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Personal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio del control de asistencia - Sprint 2.
 *
 * Concentra las reglas de HU-05 (marcacion) y la parte de HU-07 que se
 * evalua en el momento de marcar: la tardanza. La generacion de faltas al
 * cierre de la jornada vive en CierreJornadaService, porque la dispara una
 * tarea programada y no una accion del usuario.
 *
 * Toda escritura corre en transaccion (RNF-08, RNF-14) y queda auditada
 * (RNF-10).
 */
class AsistenciaService
{
    /** Codigo SQLSTATE que emiten los disparadores de la base con SIGNAL. */
    private const SQLSTATE_TRIGGER = '45000';

    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * HU-05: registra la entrada del trabajador.
     *
     * Reglas aplicadas:
     *   - solo personal registrado y ACTIVO puede marcar (CA-HU05-02);
     *   - no se admite una segunda entrada el mismo dia (CA-HU05-03);
     *   - la tardanza se evalua contra el horario asignado (CA-HU07-01);
     *   - sin horario asignado la marcacion se guarda como PUNTUAL y se
     *     advierte al operador, porque el sistema no tiene contra que
     *     comparar (CA-HU16-03).
     */
    public function registrarEntrada(
        Personal $personal,
        string $fecha,
        string $horaEntrada,
        ?string $observacion = null
    ): Asistencia {
        $this->verificarPersonalActivo($personal);

        if ($this->jornadaDe($personal, $fecha) !== null) {
            throw new ReglaNegocioException(
                "{$personal->nombre_completo} ya tiene una jornada registrada el "
                .Carbon::parse($fecha)->format('d/m/Y').'. Use la correccion manual si necesita modificarla.'
            );
        }

        $horario = $personal->horario;
        $minutosTardanza = $horario?->minutosTardanza($horaEntrada) ?? 0;

        return $this->enTransaccion(function () use ($personal, $fecha, $horaEntrada, $observacion, $minutosTardanza): Asistencia {
            $asistencia = Asistencia::create([
                'personal_id' => $personal->personal_id,
                'fecha' => $fecha,
                'hora_entrada' => $horaEntrada,
                'hora_salida' => null,
                'estado' => $minutosTardanza > 0 ? Asistencia::TARDANZA : Asistencia::PUNTUAL,
                'minutos_tardanza' => $minutosTardanza,
                'origen' => Asistencia::ORIGEN_MANUAL,
                'observacion' => $observacion,
            ]);

            $this->auditoria->registrar(
                entidad: 'asistencia',
                registroId: (int) $asistencia->asistencia_id,
                accion: 'REGISTRAR_ENTRADA',
                detalle: sprintf(
                    'Entrada %s del DNI %s. Estado: %s%s',
                    $horaEntrada,
                    $personal->dni,
                    $asistencia->estado,
                    $minutosTardanza > 0 ? " ({$minutosTardanza} min de tardanza)" : ''
                )
            );

            return $asistencia;
        });
    }

    /**
     * HU-05: registra la salida del trabajador.
     *
     * Exige que exista la entrada del mismo dia y que la salida no sea
     * anterior, tal como impone la restriccion ck_asistencia_salida.
     */
    public function registrarSalida(Personal $personal, string $fecha, string $horaSalida): Asistencia
    {
        $asistencia = $this->jornadaDe($personal, $fecha);

        if ($asistencia === null) {
            throw new ReglaNegocioException(
                "{$personal->nombre_completo} no tiene entrada registrada el "
                .Carbon::parse($fecha)->format('d/m/Y').'. Registre primero la entrada.'
            );
        }

        if ($asistencia->hora_entrada === null) {
            throw new ReglaNegocioException(
                'La jornada esta marcada como '.$asistencia->estado
                .', no tiene hora de entrada y por lo tanto no admite salida.'
            );
        }

        if ($asistencia->hora_salida !== null) {
            throw new ReglaNegocioException(
                'La salida de esa jornada ya fue registrada a las '.$asistencia->salida_corta.'.'
            );
        }

        if ($horaSalida < substr((string) $asistencia->hora_entrada, 0, 8)) {
            throw new ReglaNegocioException(
                'La hora de salida no puede ser anterior a la de entrada ('.$asistencia->entrada_corta.').'
            );
        }

        return $this->enTransaccion(function () use ($asistencia, $personal, $horaSalida): Asistencia {
            $asistencia->hora_salida = $horaSalida;
            $asistencia->save();

            $this->auditoria->registrar(
                entidad: 'asistencia',
                registroId: (int) $asistencia->asistencia_id,
                accion: 'REGISTRAR_SALIDA',
                detalle: "Salida {$horaSalida} del DNI {$personal->dni}. "
                    ."Horas trabajadas: {$asistencia->horas_trabajadas}"
            );

            return $asistencia;
        });
    }

    /**
     * Correccion manual de una jornada ya registrada.
     *
     * No corresponde a una HU del backlog, pero sin ella cualquier error de
     * digitacion quedaria fijado en el padron de asistencia, y el criterio
     * CA-HU07-03 exige que los registros sean confiables. Solo el rol con
     * permiso ASISTENCIA.ESCRIBIR puede usarla y todo cambio se audita.
     *
     * @param  array<string, mixed>  $datos
     */
    public function corregir(Asistencia $asistencia, array $datos): Asistencia
    {
        $estado = (string) $datos['estado'];
        $entrada = $datos['hora_entrada'] ?: null;
        $salida = $datos['hora_salida'] ?: null;

        $this->verificarCoherencia($estado, $entrada, $salida);

        $minutos = 0;

        if ($estado === Asistencia::TARDANZA) {
            $horario = $asistencia->personal?->horario;
            $minutos = $horario?->minutosTardanza((string) $entrada) ?? 0;

            if ($minutos === 0) {
                throw new ReglaNegocioException(
                    'Para marcar TARDANZA la hora de entrada debe superar la hora limite del horario asignado. '
                    .($horario === null
                        ? 'El trabajador no tiene horario asignado (HU-16).'
                        : "Su hora limite es {$horario->hora_limite}.")
                );
            }
        }

        return $this->enTransaccion(function () use ($asistencia, $estado, $entrada, $salida, $minutos, $datos): Asistencia {
            $antes = $asistencia->estado;

            $asistencia->fill([
                'hora_entrada' => $entrada,
                'hora_salida' => $salida,
                'estado' => $estado,
                'minutos_tardanza' => $minutos,
                'observacion' => $datos['observacion'] ?? null,
            ]);
            $asistencia->save();

            $this->auditoria->registrar(
                entidad: 'asistencia',
                registroId: (int) $asistencia->asistencia_id,
                accion: 'CORREGIR_ASISTENCIA',
                detalle: "Correccion manual de la jornada. Estado: {$antes} -> {$estado}"
            );

            return $asistencia;
        });
    }

    /**
     * Totales del periodo consultado (CA-HU06-02 y CA-HU14-03).
     *
     * @param  \Illuminate\Support\Collection<int, Asistencia>|\Illuminate\Database\Eloquent\Collection  $registros
     * @return array<string, int|float>
     */
    public function resumen($registros): array
    {
        $total = $registros->count();
        $cumplidas = $registros->whereIn('estado', [Asistencia::PUNTUAL, Asistencia::JUSTIFICADO])->count();

        return [
            'total' => $total,
            'puntuales' => $registros->where('estado', Asistencia::PUNTUAL)->count(),
            'tardanzas' => $registros->where('estado', Asistencia::TARDANZA)->count(),
            'faltas' => $registros->where('estado', Asistencia::FALTA)->count(),
            'justificados' => $registros->where('estado', Asistencia::JUSTIFICADO)->count(),
            'minutos_tardanza' => (int) $registros->sum('minutos_tardanza'),
            'cumplimiento' => $total > 0 ? round(100 * $cumplidas / $total, 2) : 0.0,
        ];
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /** Jornada del trabajador en la fecha indicada, si existe. */
    public function jornadaDe(Personal $personal, string $fecha): ?Asistencia
    {
        return Asistencia::where('personal_id', $personal->personal_id)
            ->whereDate('fecha', $fecha)
            ->first();
    }

    private function verificarPersonalActivo(Personal $personal): void
    {
        if (! $personal->es_activo) {
            throw new ReglaNegocioException(
                "{$personal->nombre_completo} esta INACTIVO en el padron y no puede registrar asistencia."
            );
        }
    }

    /**
     * Refleja en la aplicacion la restriccion ck_asistencia_coherencia de
     * la base, para dar un mensaje entendible antes de que MySQL rechace
     * la fila con un error tecnico.
     */
    private function verificarCoherencia(string $estado, ?string $entrada, ?string $salida): void
    {
        $exigeEntrada = in_array($estado, Asistencia::ESTADOS_CON_ENTRADA, true);

        if ($exigeEntrada && $entrada === null) {
            throw new ReglaNegocioException("El estado {$estado} exige una hora de entrada.");
        }

        if (! $exigeEntrada && ($entrada !== null || $salida !== null)) {
            throw new ReglaNegocioException(
                "El estado {$estado} no admite horas de entrada ni de salida: el trabajador no se presento."
            );
        }

        if ($entrada !== null && $salida !== null && $salida < $entrada) {
            throw new ReglaNegocioException('La hora de salida no puede ser anterior a la de entrada.');
        }
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

            // Violacion del indice unico (personal_id, fecha).
            if ((string) $e->getCode() === '23000') {
                throw new ReglaNegocioException(
                    'Ya existe un registro de asistencia para ese trabajador en esa fecha.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }
}
