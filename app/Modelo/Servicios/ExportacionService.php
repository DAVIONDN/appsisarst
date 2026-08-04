<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Reporte;
use App\Modelo\Reportes\Exportador;
use App\Modelo\Reportes\Exportadores\ExportadorExcel;
use App\Modelo\Reportes\Exportadores\ExportadorPdf;
use App\Modelo\Reportes\ResultadoReporte;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contexto del patron Estrategia de exportacion (RF-18).
 *
 * Es la clase que, dado un formato, elige la estrategia concreta. Los
 * generadores de reportes no conocen ninguna implementacion: solo
 * producen un ResultadoReporte y lo entregan aqui.
 *
 * Para agregar un formato nuevo basta con:
 *   1. escribir una clase que implemente la interfaz Exportador;
 *   2. agregarla al arreglo de $estrategias.
 * Ningun generador ni controlador cambia. Es exactamente lo que persigue
 * la Figura 5.5 del documento de diseno.
 */
class ExportacionService
{
    public const PANTALLA = 'PANTALLA';

    /** @var array<int, Exportador> */
    private array $estrategias;

    public function __construct(private readonly AuditoriaService $auditoria)
    {
        $this->estrategias = [
            new ExportadorPdf(),
            new ExportadorExcel(),
        ];
    }

    /**
     * Formatos disponibles, para dibujar los botones de la interfaz.
     *
     * @return array<int, Exportador>
     */
    public function disponibles(): array
    {
        return $this->estrategias;
    }

    /**
     * @return array<int, string>
     */
    public function formatos(): array
    {
        return array_map(fn (Exportador $e): string => $e->formato(), $this->estrategias);
    }

    /**
     * Exporta el reporte en el formato solicitado.
     *
     * @param  string  $formato  PDF o EXCEL.
     */
    public function exportar(ResultadoReporte $reporte, string $formato): Response
    {
        $estrategia = $this->estrategiaPara($formato);

        // Un reporte vacio produciria un archivo con solo el encabezado.
        // Es mejor avisarlo que entregar un archivo inutil (CA-HU13-01).
        if ($reporte->estaVacio()) {
            throw new ReglaNegocioException(
                'No hay datos que exportar con los filtros aplicados. Ajuste los filtros e intente de nuevo.'
            );
        }

        $this->registrarGeneracion($reporte, $estrategia->formato());

        return $estrategia->exportar($reporte);
    }

    /**
     * Deja constancia del reporte generado.
     *
     * La tabla "reporte" del modelo entidad-relacion existe para esto:
     * guarda quien lo genero, de que tipo, con que filtros y en que
     * formato. Es la trazabilidad que exige el RNF-10 sobre quien consulto
     * que informacion del personal (RNF-12, Ley 29733).
     */
    public function registrarGeneracion(ResultadoReporte $reporte, string $formato): void
    {
        $usuarioId = Auth::id();

        if ($usuarioId === null) {
            return;
        }

        Reporte::create([
            'usuario_id' => $usuarioId,
            'tipo' => $reporte->tipo,
            'parametros' => [
                'filtros' => $reporte->filtros,
                'registros' => $reporte->cantidadFilas(),
            ],
            'formato' => $formato,
            'fecha_generacion' => now(),
        ]);

        $this->auditoria->registrar(
            entidad: 'reporte',
            registroId: null,
            accion: 'GENERAR_REPORTE',
            detalle: sprintf(
                'Reporte %s en formato %s con %d registro(s). %s',
                $reporte->tipo,
                $formato,
                $reporte->cantidadFilas(),
                $reporte->filtrosLegibles()
            )
        );
    }

    private function estrategiaPara(string $formato): Exportador
    {
        $formato = mb_strtoupper(trim($formato));

        foreach ($this->estrategias as $estrategia) {
            if ($estrategia->formato() === $formato) {
                return $estrategia;
            }
        }

        throw new ReglaNegocioException(
            "Formato de exportacion no soportado: {$formato}. Disponibles: "
            .implode(', ', $this->formatos()).'.'
        );
    }
}
