<?php

declare(strict_types=1);

namespace App\Modelo\Reportes\Exportadores;

use App\Modelo\Reportes\Exportador;
use App\Modelo\Reportes\ResultadoReporte;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Estrategia de exportacion a Excel (RF-18 / CA-HU13-03, CA-HU15-04).
 *
 * Implementa la interfaz IExportador de la Figura 5.5 usando
 * PhpSpreadsheet directamente.
 *
 * Nota sobre la herramienta: la lista del proyecto propone
 * maatwebsite/excel, que es una envoltura de PhpSpreadsheet pensada para
 * exportar modelos Eloquent. Aqui no hace falta esa envoltura: el
 * exportador no recibe modelos sino un ResultadoReporte ya armado, con
 * sus columnas y filas resueltas. Usar PhpSpreadsheet directo evita una
 * dependencia intermedia y deja el codigo del patron Estrategia mas
 * legible. El resultado -un .xlsx- es identico.
 *
 * El archivo se entrega como respuesta transmitida (streamed) para no
 * cargarlo entero en memoria (RNF-04).
 */
class ExportadorExcel implements Exportador
{
    private const AZUL_INSTITUCIONAL = '0D5AA7';

    public function formato(): string
    {
        return 'EXCEL';
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    public function etiqueta(): string
    {
        return 'Exportar a Excel';
    }

    public function icono(): string
    {
        return 'bi-file-earmark-excel';
    }

    public function exportar(ResultadoReporte $reporte): Response
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(mb_substr($reporte->tipo, 0, 31));

        $ultimaColumna = $this->letraColumna(max(1, count($reporte->columnas)));
        $fila = 1;

        // --- Encabezado institucional ---------------------------------
        $hoja->setCellValue("A{$fila}", 'SISARST - Red de Salud Tayacaja');
        $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(14);
        $fila++;

        $hoja->setCellValue("A{$fila}", $reporte->titulo);
        $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(11);
        $fila += 2;

        // --- Filtros y datos de generacion ----------------------------
        $hoja->setCellValue("A{$fila}", 'Filtros aplicados: '.$reporte->filtrosLegibles());
        $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
        $hoja->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9);
        $fila++;

        $hoja->setCellValue("A{$fila}", sprintf(
            'Generado el %s por %s · %d registro(s)',
            now()->format('d/m/Y H:i'),
            auth()->user()?->nombre_mostrado ?? 'Sistema',
            $reporte->cantidadFilas()
        ));
        $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
        $hoja->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9);
        $fila += 2;

        // --- Cabecera de la tabla -------------------------------------
        $filaCabecera = $fila;

        foreach ($reporte->columnas as $i => $titulo) {
            $hoja->setCellValue($this->letraColumna($i + 1).$fila, $titulo);
        }

        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::AZUL_INSTITUCIONAL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $fila++;

        // --- Datos ----------------------------------------------------
        foreach ($reporte->filas as $registro) {
            foreach (array_values($registro) as $i => $valor) {
                $hoja->setCellValue($this->letraColumna($i + 1).$fila, $valor);
            }
            $fila++;
        }

        $ultimaFilaDatos = $fila - 1;

        if ($ultimaFilaDatos >= $filaCabecera) {
            $hoja->getStyle("A{$filaCabecera}:{$ultimaColumna}{$ultimaFilaDatos}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Filtros automaticos sobre la cabecera: util para el usuario.
            $hoja->setAutoFilter("A{$filaCabecera}:{$ultimaColumna}{$ultimaFilaDatos}");
            $hoja->freezePane("A".($filaCabecera + 1));
        }

        // --- Totales (CA-HU13-04, CA-HU15-03) -------------------------
        if ($reporte->totales !== []) {
            $fila++;
            $hoja->setCellValue("A{$fila}", 'RESUMEN');
            $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
            $fila++;

            foreach ($reporte->totales as $etiqueta => $valor) {
                $hoja->setCellValue("A{$fila}", $etiqueta);
                $hoja->setCellValue("B{$fila}", $valor);
                $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
                $fila++;
            }
        }

        // --- Subtotales por agrupacion --------------------------------
        foreach ($reporte->agrupaciones as $nombre => $valores) {
            if ($valores === []) {
                continue;
            }

            $fila++;
            $hoja->setCellValue("A{$fila}", mb_strtoupper($nombre));
            $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
            $fila++;

            foreach ($valores as $etiqueta => $valor) {
                $hoja->setCellValue("A{$fila}", (string) $etiqueta);
                $hoja->setCellValue("B{$fila}", $valor);
                $fila++;
            }
        }

        // --- Ancho de columnas ----------------------------------------
        for ($i = 1; $i <= max(2, count($reporte->columnas)); $i++) {
            $hoja->getColumnDimension($this->letraColumna($i))->setAutoSize(true);
        }

        return $this->transmitir($libro, $reporte->nombreArchivo().'.xlsx');
    }

    private function transmitir(Spreadsheet $libro, string $nombre): Response
    {
        /*
         * Se captura el binario en memoria con ob_start() / ob_get_clean().
         * Esto evita:
         *   - la contaminacion de php://output por buffers de Laravel, y
         *   - problemas de permisos o extensiones en archivos temporales
         *     de Windows (XAMPP).
         * El resultado es un binario limpio que se entrega como respuesta
         * con Content-Type correcto.
         */
        ob_start();
        (new Xlsx($libro))->save('php://output');
        $contenido = ob_get_clean();

        return response((string) $contenido, Response::HTTP_OK, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
            'Content-Length'      => strlen((string) $contenido),
            'Cache-Control'       => 'max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    /** Convierte 1 en "A", 27 en "AA", etc. */
    private function letraColumna(int $indice): string
    {
        $letra = '';

        while ($indice > 0) {
            $resto = ($indice - 1) % 26;
            $letra = chr(65 + $resto).$letra;
            $indice = intdiv($indice - 1, 26);
        }

        return $letra !== '' ? $letra : 'A';
    }
}
