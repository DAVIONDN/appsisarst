<?php

declare(strict_types=1);

namespace App\Modelo\Reportes\Exportadores;

use App\Modelo\Reportes\Exportador;
use App\Modelo\Reportes\ResultadoReporte;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Estrategia de exportacion a PDF (RF-18 / CA-HU13-03, CA-HU15-04).
 *
 * Implementa la interfaz IExportador de la Figura 5.5 usando
 * barryvdh/laravel-dompdf, el paquete que fija la lista de herramientas
 * del proyecto.
 *
 * La orientacion se elige segun la cantidad de columnas: con mas de seis,
 * el vertical corta la tabla.
 */
class ExportadorPdf implements Exportador
{
    /** A partir de esta cantidad de columnas conviene el apaisado. */
    private const COLUMNAS_APAISADO = 6;

    /** A partir de aqui hay que achicar la letra para que todo entre. */
    private const COLUMNAS_LETRA_CHICA = 9;

    public function formato(): string
    {
        return 'PDF';
    }

    public function extension(): string
    {
        return 'pdf';
    }

    public function etiqueta(): string
    {
        return 'Exportar a PDF';
    }

    public function icono(): string
    {
        return 'bi-file-earmark-pdf';
    }

    public function exportar(ResultadoReporte $reporte): Response
    {
        $columnas = count($reporte->columnas);

        $pdf = Pdf::loadView('reporte.pdf.documento', [
            'reporte' => $reporte,
            'generadoPor' => auth()->user()?->nombre_mostrado ?? 'Sistema',
            'generadoEn' => now(),
            // Con muchas columnas la letra debe achicarse; la plantilla usa
            // ademas table-layout fijo para que el texto se parta en lugar
            // de desbordar el papel.
            'tamanoLetra' => $columnas >= self::COLUMNAS_LETRA_CHICA ? 6.5 : 7.5,
        ])->setPaper('a4', $columnas > self::COLUMNAS_APAISADO ? 'landscape' : 'portrait');

        // Limpiar buffers acumulados (BOM, notices) antes de enviar el PDF.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return $pdf->download($reporte->nombreArchivo().'.pdf');
    }
}
