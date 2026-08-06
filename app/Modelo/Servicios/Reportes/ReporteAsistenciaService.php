<?php

declare(strict_types=1);

namespace App\Modelo\Servicios\Reportes;

use App\Modelo\Area;
use App\Modelo\Asistencia;
use App\Modelo\Personal;
use App\Modelo\Reportes\ResultadoReporte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Generador del reporte de asistencia - HU-14 (RF-16).
 *
 * Criterios cubiertos:
 *   CA-HU14-01  rango de fechas, area y personal
 *   CA-HU14-02  diferencia asistencias, tardanzas y faltas
 *   CA-HU14-03  porcentaje de cumplimiento por trabajador
 *
 * A diferencia de la consulta operativa del Sprint 2, que lista jornada
 * por jornada, este reporte esta CONSOLIDADO POR TRABAJADOR: una fila por
 * persona con sus totales del periodo. Es lo que pide el criterio
 * CA-HU14-03, que exige un porcentaje "de cada trabajador".
 */
class ReporteAsistenciaService
{
    public const TIPO = 'ASISTENCIA';

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function generar(array $filtros, ?int $areaRestringida = null): ResultadoReporte
    {
        $desde = $filtros['desde'] ?? now()->startOfMonth()->toDateString();
        $hasta = $filtros['hasta'] ?? now()->toDateString();

        $registros = $this->consulta($filtros, $areaRestringida, (string) $desde, (string) $hasta)
            ->with(['personal.area.establecimiento'])
            ->get();

        // Consolidado por trabajador (CA-HU14-03).
        $porTrabajador = $registros
            ->groupBy('personal_id')
            ->map(fn (Collection $jornadas) => $this->resumirTrabajador($jornadas))
            ->sortBy('trabajador')
            ->values();

        $filas = $porTrabajador->map(fn (array $r): array => [
            $r['dni'],
            $r['trabajador'],
            $r['area'],
            $r['registros'],
            $r['puntuales'],
            $r['tardanzas'],
            $r['faltas'],
            $r['justificados'],
            $r['minutos'],
            $r['cumplimiento'].'%',
        ]);

        $total = $registros->count();
        $cumplidas = $registros
            ->whereIn('estado', [Asistencia::PUNTUAL, Asistencia::JUSTIFICADO])
            ->count();

        return new ResultadoReporte(
            tipo: self::TIPO,
            titulo: sprintf(
                'Reporte de Asistencia · %s al %s',
                Carbon::parse($desde)->format('d/m/Y'),
                Carbon::parse($hasta)->format('d/m/Y')
            ),
            columnas: [
                'DNI', 'Trabajador', 'Area', 'Jornadas',
                'Puntuales', 'Tardanzas', 'Faltas', 'Justificados',
                'Minutos de tardanza', 'Cumplimiento',
            ],
            filas: $filas,
            filtros: $this->filtrosLegibles($filtros, (string) $desde, (string) $hasta),
            totales: [
                'Trabajadores en el reporte' => $porTrabajador->count(),
                'Jornadas registradas' => $total,
                'Puntuales' => $registros->where('estado', Asistencia::PUNTUAL)->count(),
                'Tardanzas' => $registros->where('estado', Asistencia::TARDANZA)->count(),
                'Faltas' => $registros->where('estado', Asistencia::FALTA)->count(),
                'Justificados' => $registros->where('estado', Asistencia::JUSTIFICADO)->count(),
                'Minutos de tardanza acumulados' => (int) $registros->sum('minutos_tardanza'),
                'Cumplimiento general' => ($total > 0 ? round(100 * $cumplidas / $total, 2) : 0).'%',
            ],
            agrupaciones: [
                'Jornadas por area' => $registros
                    ->groupBy(fn (Asistencia $a) => $a->personal?->area?->nombre ?? 'Sin area')
                    ->map->count()
                    ->sortDesc()
                    ->all(),
                'Jornadas por estado' => collect(Asistencia::ESTADOS)
                    ->mapWithKeys(fn (string $e) => [$e => $registros->where('estado', $e)->count()])
                    ->filter(fn (int $n) => $n > 0)
                    ->all(),
            ],
        );
    }

    /**
     * Resumen de un trabajador dentro del periodo.
     *
     * @param  Collection<int, Asistencia>  $jornadas
     * @return array<string, string|int|float>
     */
    private function resumirTrabajador(Collection $jornadas): array
    {
        /** @var Personal|null $personal */
        $personal = $jornadas->first()?->personal;

        $total = $jornadas->count();

        // El cumplimiento cuenta como favorables las jornadas puntuales y
        // las justificadas: una licencia aprobada no es un incumplimiento
        // del trabajador.
        $cumplidas = $jornadas
            ->whereIn('estado', [Asistencia::PUNTUAL, Asistencia::JUSTIFICADO])
            ->count();

        return [
            'dni' => $personal?->dni ?? '—',
            'trabajador' => $personal?->nombre_completo ?? '—',
            'area' => $personal?->area?->nombre ?? '—',
            'registros' => $total,
            'puntuales' => $jornadas->where('estado', Asistencia::PUNTUAL)->count(),
            'tardanzas' => $jornadas->where('estado', Asistencia::TARDANZA)->count(),
            'faltas' => $jornadas->where('estado', Asistencia::FALTA)->count(),
            'justificados' => $jornadas->where('estado', Asistencia::JUSTIFICADO)->count(),
            'minutos' => (int) $jornadas->sum('minutos_tardanza'),
            'cumplimiento' => $total > 0 ? round(100 * $cumplidas / $total, 2) : 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros, ?int $areaRestringida, string $desde, string $hasta): Builder
    {
        return Asistencia::query()
            ->entreFechas($desde, $hasta)
            ->when(
                $areaRestringida,
                fn (Builder $q) => $q->whereHas('personal', fn (Builder $p) => $p->where('area_id', $areaRestringida))
            )
            ->dePersonal($filtros['personal_id'] ?? null)
            ->deArea($filtros['area_id'] ?? null)
            ->deEstado($filtros['estado'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, string>
     */
    private function filtrosLegibles(array $filtros, string $desde, string $hasta): array
    {
        $legibles = [
            'Periodo' => Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y'),
        ];

        if (! empty($filtros['area_id'])) {
            $legibles['Area'] = (string) Area::where('area_id', $filtros['area_id'])->value('nombre');
        }

        if (! empty($filtros['personal_id'])) {
            $p = Personal::find($filtros['personal_id']);
            $legibles['Trabajador'] = $p?->nombre_completo ?? '—';
        }

        if (! empty($filtros['estado'])) {
            $legibles['Estado'] = (string) $filtros['estado'];
        }

        return $legibles;
    }
}
