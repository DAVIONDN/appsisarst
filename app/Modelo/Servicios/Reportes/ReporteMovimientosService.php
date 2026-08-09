<?php

declare(strict_types=1);

namespace App\Modelo\Servicios\Reportes;

use App\Modelo\Area;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use App\Modelo\Reportes\ResultadoReporte;
use App\Modelo\TipoMovimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Generador del reporte de movimientos institucionales - HU-15 (RF-17).
 *
 * Criterios cubiertos:
 *   CA-HU15-01  filtros por tipo, personal, area y periodo
 *   CA-HU15-02  muestra el estado vigente de cada movimiento
 *   CA-HU15-03  totales consolidados por tipo de movimiento
 */
class ReporteMovimientosService
{
    public const TIPO = 'MOVIMIENTOS';

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function generar(array $filtros, ?int $areaRestringida = null): ResultadoReporte
    {
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;

        $movimientos = $this->consulta($filtros, $areaRestringida)
            ->with(['personal.area.establecimiento', 'tipo', 'establecimientoDestino'])
            ->orderByDesc('fecha_inicio')
            ->orderBy('personal_id')
            ->get();

        $filas = $movimientos->map(fn (MovimientoInstitucional $m): array => [
            $m->personal?->dni ?? '—',
            $m->personal?->nombre_completo ?? '—',
            $m->personal?->area?->nombre ?? '—',
            str_replace('_', ' ', $m->tipo?->nombre ?? '—'),
            $m->fecha_inicio?->format('d/m/Y') ?? '—',
            $m->fecha_fin?->format('d/m/Y') ?? '—',
            $m->dias,
            $m->establecimientoDestino?->nombre ?? '—',
            $m->motivo,
            $m->estado,
            $m->situacion,
        ]);

        return new ResultadoReporte(
            tipo: self::TIPO,
            titulo: $this->titulo($desde, $hasta),
            columnas: [
                'DNI', 'Trabajador', 'Area de origen', 'Tipo',
                'Desde', 'Hasta', 'Dias', 'Establecimiento de destino',
                'Motivo', 'Estado', 'Situacion',
            ],
            filas: $filas,
            filtros: $this->filtrosLegibles($filtros),
            totales: [
                'Total de movimientos' => $movimientos->count(),
                'Trabajadores involucrados' => $movimientos->pluck('personal_id')->unique()->count(),
                'Dias acumulados' => $movimientos->sum(fn (MovimientoInstitucional $m) => $m->dias),
                'Pendientes' => $movimientos->where('estado', MovimientoInstitucional::PENDIENTE)->count(),
                'Aprobados' => $movimientos->where('estado', MovimientoInstitucional::APROBADO)->count(),
                'Rechazados' => $movimientos->where('estado', MovimientoInstitucional::RECHAZADO)->count(),
                'Finalizados' => $movimientos->where('estado', MovimientoInstitucional::FINALIZADO)->count(),
            ],
            agrupaciones: [
                // CA-HU15-03: totales consolidados por tipo.
                'Total por tipo de movimiento' => $movimientos
                    ->groupBy(fn (MovimientoInstitucional $m) => str_replace('_', ' ', $m->tipo?->nombre ?? 'Sin tipo'))
                    ->map->count()
                    ->sortDesc()
                    ->all(),
                'Dias por tipo de movimiento' => $movimientos
                    ->groupBy(fn (MovimientoInstitucional $m) => str_replace('_', ' ', $m->tipo?->nombre ?? 'Sin tipo'))
                    ->map(fn ($grupo) => $grupo->sum(fn (MovimientoInstitucional $m) => $m->dias))
                    ->sortDesc()
                    ->all(),
                'Total por area' => $movimientos
                    ->groupBy(fn (MovimientoInstitucional $m) => $m->personal?->area?->nombre ?? 'Sin area')
                    ->map->count()
                    ->sortDesc()
                    ->all(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros, ?int $areaRestringida): Builder
    {
        return MovimientoInstitucional::query()
            ->when(
                $areaRestringida,
                fn (Builder $q) => $q->whereHas('personal', fn (Builder $p) => $p->where('area_id', $areaRestringida))
            )
            ->dePersonal($filtros['personal_id'] ?? null)
            ->deTipo($filtros['tipo_movimiento_id'] ?? null)
            ->deEstado($filtros['estado'] ?? null)
            ->deArea($filtros['area_id'] ?? null)
            ->enPeriodo($filtros['desde'] ?? null, $filtros['hasta'] ?? null);
    }

    private function titulo(?string $desde, ?string $hasta): string
    {
        if ($desde === null && $hasta === null) {
            return 'Reporte de Movimientos Institucionales · Historico completo';
        }

        return sprintf(
            'Reporte de Movimientos Institucionales · %s al %s',
            $desde !== null ? Carbon::parse($desde)->format('d/m/Y') : 'inicio',
            $hasta !== null ? Carbon::parse($hasta)->format('d/m/Y') : 'hoy'
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, string>
     */
    private function filtrosLegibles(array $filtros): array
    {
        $legibles = [];

        if (! empty($filtros['desde']) || ! empty($filtros['hasta'])) {
            $legibles['Periodo'] = sprintf(
                '%s al %s',
                ! empty($filtros['desde']) ? Carbon::parse($filtros['desde'])->format('d/m/Y') : 'inicio',
                ! empty($filtros['hasta']) ? Carbon::parse($filtros['hasta'])->format('d/m/Y') : 'hoy'
            );
        }

        if (! empty($filtros['tipo_movimiento_id'])) {
            $nombre = (string) TipoMovimiento::where(
                'tipo_movimiento_id',
                $filtros['tipo_movimiento_id']
            )->value('nombre');
            $legibles['Tipo'] = str_replace('_', ' ', $nombre);
        }

        if (! empty($filtros['area_id'])) {
            $legibles['Area'] = (string) Area::where('area_id', $filtros['area_id'])->value('nombre');
        }

        if (! empty($filtros['personal_id'])) {
            $legibles['Trabajador'] = Personal::find($filtros['personal_id'])?->nombre_completo ?? '—';
        }

        $legibles['Estado'] = ! empty($filtros['estado']) ? (string) $filtros['estado'] : 'Todos';

        return $legibles;
    }
}
