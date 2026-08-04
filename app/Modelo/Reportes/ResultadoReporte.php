<?php

declare(strict_types=1);

namespace App\Modelo\Reportes;

use Illuminate\Support\Collection;

/**
 * Capa MODELO - Resultado de un reporte generado.
 *
 * Es el objeto que los tres generadores devuelven y que los exportadores
 * consumen. Su razon de ser es desacoplar ambos lados: gracias a el,
 * agregar un formato de exportacion nuevo no obliga a tocar ningun
 * generador, y agregar un reporte nuevo no obliga a tocar ningun
 * exportador. Es la pieza que hace posible el patron Estrategia de la
 * Figura 5.5 del documento de diseno.
 *
 * No conoce HTTP ni Blade: solo describe QUE se reporta, no COMO se
 * muestra.
 */
final class ResultadoReporte
{
    /**
     * @param  string  $tipo  PERSONAL, ASISTENCIA o MOVIMIENTOS.
     * @param  string  $titulo  Encabezado del reporte.
     * @param  array<int, string>  $columnas  Titulos de las columnas, en orden.
     * @param  Collection<int, array<int, string|int|float|null>>  $filas  Datos, alineados con $columnas.
     * @param  array<string, string>  $filtros  Filtros aplicados, ya legibles para el lector.
     * @param  array<string, int|float|string>  $totales  Indicadores consolidados.
     * @param  array<string, array<string, int|float>>  $agrupaciones  Subtotales por criterio.
     */
    public function __construct(
        public readonly string $tipo,
        public readonly string $titulo,
        public readonly array $columnas,
        public readonly Collection $filas,
        public readonly array $filtros = [],
        public readonly array $totales = [],
        public readonly array $agrupaciones = [],
    ) {
    }

    public function estaVacio(): bool
    {
        return $this->filas->isEmpty();
    }

    public function cantidadFilas(): int
    {
        return $this->filas->count();
    }

    /**
     * Nombre sugerido del archivo, sin extension.
     * Ej.: "SISARST_reporte_personal_2026-08-10_1432"
     */
    public function nombreArchivo(): string
    {
        return sprintf(
            'SISARST_reporte_%s_%s',
            mb_strtolower($this->tipo),
            now()->format('Y-m-d_Hi')
        );
    }

    /** Filtros en una sola linea, para el encabezado de los archivos. */
    public function filtrosLegibles(): string
    {
        if ($this->filtros === []) {
            return 'Sin filtros: se incluye toda la informacion disponible.';
        }

        return collect($this->filtros)
            ->map(fn (string $valor, string $etiqueta): string => "{$etiqueta}: {$valor}")
            ->implode(' · ');
    }
}
