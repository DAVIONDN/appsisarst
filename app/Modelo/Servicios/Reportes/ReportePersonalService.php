<?php

declare(strict_types=1);

namespace App\Modelo\Servicios\Reportes;

use App\Modelo\Area;
use App\Modelo\Establecimiento;
use App\Modelo\Personal;
use App\Modelo\Reportes\ResultadoReporte;
use Illuminate\Database\Eloquent\Builder;

/**
 * Generador del reporte de personal - HU-13 (RF-15).
 *
 * Criterios cubiertos:
 *   CA-HU13-01  filtros por area, cargo, condicion y establecimiento
 *   CA-HU13-02  los datos coinciden con los registros vigentes del padron
 *   CA-HU13-04  totales por condicion y por area
 *
 * El servicio no sabe nada de PDF ni de Excel: produce un
 * ResultadoReporte y lo entrega. La exportacion es responsabilidad de
 * ExportacionService (patron Estrategia, Figura 5.5).
 */
class ReportePersonalService
{
    public const TIPO = 'PERSONAL';

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function generar(array $filtros, ?int $areaRestringida = null): ResultadoReporte
    {
        $consulta = $this->consulta($filtros, $areaRestringida);

        $personal = $consulta
            ->with(['area.establecimiento', 'horario'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $filas = $personal->map(fn (Personal $p): array => [
            $p->dni,
            $p->nombre_completo,
            $p->cargo,
            $p->condicion_laboral,
            $p->area?->nombre ?? '—',
            $p->area?->establecimiento?->nombre ?? '—',
            $p->area?->establecimiento?->microrred ?? '—',
            $p->horario?->nombre ?? 'Sin asignar',
            $p->fecha_ingreso?->format('d/m/Y') ?? '—',
            $p->anios_servicio,
            $p->estado,
        ]);

        return new ResultadoReporte(
            tipo: self::TIPO,
            titulo: 'Reporte del Padron de Personal',
            columnas: [
                'DNI', 'Apellidos y nombres', 'Cargo', 'Condicion laboral',
                'Area', 'Establecimiento', 'Microrred', 'Horario',
                'Fecha de ingreso', 'Anios de servicio', 'Estado',
            ],
            filas: $filas,
            filtros: $this->filtrosLegibles($filtros),
            totales: [
                'Total de trabajadores' => $personal->count(),
                'Activos' => $personal->where('estado', Personal::ESTADO_ACTIVO)->count(),
                'Inactivos' => $personal->where('estado', Personal::ESTADO_INACTIVO)->count(),
                'Sin horario asignado' => $personal->whereNull('horario_id')->count(),
            ],
            // CA-HU13-04: totales por condicion y por area.
            agrupaciones: [
                'Total por condicion laboral' => $personal
                    ->groupBy('condicion_laboral')
                    ->map->count()
                    ->sortDesc()
                    ->all(),
                'Total por area' => $personal
                    ->groupBy(fn (Personal $p) => $p->area?->nombre ?? 'Sin area')
                    ->map->count()
                    ->sortDesc()
                    ->all(),
                'Total por establecimiento' => $personal
                    ->groupBy(fn (Personal $p) => $p->area?->establecimiento?->nombre ?? 'Sin establecimiento')
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
        return Personal::query()
            ->when($areaRestringida, fn (Builder $q) => $q->where('personal.area_id', $areaRestringida))
            ->deEstablecimiento($filtros['establecimiento_id'] ?? null)
            ->deArea($filtros['area_id'] ?? null)
            ->deCargo($filtros['cargo'] ?? null)
            ->deCondicion($filtros['condicion_laboral'] ?? null)
            ->deEstado($filtros['estado'] ?? null);
    }

    /**
     * Traduce los identificadores de los filtros a texto legible, para que
     * el encabezado del reporte diga "Area: Enfermeria" y no "area_id: 3".
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, string>
     */
    private function filtrosLegibles(array $filtros): array
    {
        $legibles = [];

        if (! empty($filtros['establecimiento_id'])) {
            $legibles['Establecimiento'] = (string) Establecimiento::where(
                'establecimiento_id',
                $filtros['establecimiento_id']
            )->value('nombre');
        }

        if (! empty($filtros['area_id'])) {
            $legibles['Area'] = (string) Area::where('area_id', $filtros['area_id'])->value('nombre');
        }

        if (! empty($filtros['cargo'])) {
            $legibles['Cargo'] = (string) $filtros['cargo'];
        }

        if (! empty($filtros['condicion_laboral'])) {
            $legibles['Condicion laboral'] = (string) $filtros['condicion_laboral'];
        }

        $legibles['Estado'] = ! empty($filtros['estado'])
            ? (string) $filtros['estado']
            : 'Todos';

        return $legibles;
    }
}
