<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Asistencia;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de cierre de jornada - HU-07 (RF-07).
 *
 * Es la mitad automatica del requerimiento: al terminar el dia recorre al
 * personal activo con horario asignado y, para quien no marco entrada,
 * genera el registro correspondiente (CA-HU07-02).
 *
 * Antes de marcar FALTA verifica si existe un movimiento institucional
 * aprobado que cubra la fecha; en ese caso el estado es JUSTIFICADO, tal
 * como exige el diagrama DS-HU07 del documento de diseno.
 *
 * La operacion es IDEMPOTENTE (RNF-14): si ya existe una jornada para ese
 * trabajador y esa fecha, no la toca. Puede ejecutarse dos veces sin
 * duplicar ni pisar datos.
 *
 * Lo dispara el actor "Sistema" del diagrama de casos de uso, no una
 * persona: la tarea programada declarada en routes/console.php.
 */
class CierreJornadaService
{
    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    /**
     * Procesa el cierre de la fecha indicada.
     *
     * @return array{fecha: string, faltas: int, justificados: int, omitidos: int, sin_horario: int}
     */
    public function procesar(Carbon $fecha): array
    {
        $resultado = [
            'fecha' => $fecha->toDateString(),
            'faltas' => 0,
            'justificados' => 0,
            'omitidos' => 0,
            'sin_horario' => 0,
        ];

        $personal = Personal::query()
            ->activo()
            ->with('horario')
            ->orderBy('personal_id')
            ->get();

        foreach ($personal as $trabajador) {
            // CA-HU16-03: sin horario asignado el sistema no puede evaluar.
            if ($trabajador->horario === null) {
                $resultado['sin_horario']++;

                continue;
            }

            // Fuera de sus dias laborables no se evalua nada.
            if (! $trabajador->horario->esDiaLaborable($fecha)) {
                $resultado['omitidos']++;

                continue;
            }

            // Idempotencia: si la jornada ya existe, no se toca.
            if ($this->tieneJornada($trabajador, $fecha)) {
                $resultado['omitidos']++;

                continue;
            }

            $justificado = $this->tieneMovimientoVigente($trabajador, $fecha);
            $estado = $justificado ? Asistencia::JUSTIFICADO : Asistencia::FALTA;

            DB::transaction(function () use ($trabajador, $fecha, $estado, $justificado): void {
                $asistencia = Asistencia::create([
                    'personal_id' => $trabajador->personal_id,
                    'fecha' => $fecha->toDateString(),
                    'hora_entrada' => null,
                    'hora_salida' => null,
                    'estado' => $estado,
                    'minutos_tardanza' => 0,
                    'origen' => Asistencia::ORIGEN_AUTOMATICO,
                    'observacion' => $justificado
                        ? 'Generado por el cierre de jornada: movimiento institucional vigente'
                        : 'Generado por el cierre de jornada: sin marcacion de entrada',
                ]);

                $this->auditoria->registrar(
                    entidad: 'asistencia',
                    registroId: (int) $asistencia->asistencia_id,
                    accion: 'CIERRE_JORNADA',
                    detalle: "{$estado} automatico del DNI {$trabajador->dni} el {$fecha->format('d/m/Y')}"
                );
            });

            $justificado ? $resultado['justificados']++ : $resultado['faltas']++;
        }

        return $resultado;
    }

    private function tieneJornada(Personal $trabajador, Carbon $fecha): bool
    {
        return Asistencia::where('personal_id', $trabajador->personal_id)
            ->whereDate('fecha', $fecha->toDateString())
            ->exists();
    }

    /**
     * Verdadero si el trabajador tiene un movimiento APROBADO que cubre la
     * fecha (licencia, vacaciones, comision...). Los movimientos en estado
     * PENDIENTE no justifican: aun no fueron autorizados.
     */
    private function tieneMovimientoVigente(Personal $trabajador, Carbon $fecha): bool
    {
        return MovimientoInstitucional::where('personal_id', $trabajador->personal_id)
            ->where('estado', MovimientoInstitucional::APROBADO)
            ->whereDate('fecha_inicio', '<=', $fecha->toDateString())
            ->whereDate('fecha_fin', '>=', $fecha->toDateString())
            ->exists();
    }
}
