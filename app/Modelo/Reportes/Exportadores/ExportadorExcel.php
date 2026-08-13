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
         * Causa raiz del problema: php.ini de XAMPP tiene output_buffering=0
         * y display_errors=1. Con estas opciones, cualquier notice/warning de
         * PHP o de PhpSpreadsheet se imprime directamente en la respuesta HTTP
         * ANTES de que el binario xlsx pueda enviarse, corrompiendo el archivo.
         *
         * Solucion:
         *   1. ob_start() captura (y descarta luego) cualquier salida espuria
         *      que se produzca durante el save().
         *   2. El libro se guarda en storage/app/tmp (directorio Laravel
         *      garantizado como escribible, sin depender de sys_get_temp_dir).
         *   3. response()->download() usa BinaryFileResponse de Symfony, que
         *      sirve el archivo via readfile() directamente al socket, sin
         *      pasar por ningun output buffer de PHP.
         *   4. deleteFileAfterSend(true) elimina el temporal al terminar.
         */
        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmp = $dir.DIRECTORY_SEPARATOR.'sisarst_'.uniqid().'.xlsx';

        (new Xlsx($libro))->save($tmp);

        /*
         * Limpiar TODOS los buffers activos antes de enviar el binario.
         *
         * public/index.php arranca con ob_start() para retener cualquier
         * salida espuria (BOM UTF-8, notices, warnings) que se haya
         * producido durante el ciclo de la peticion. Antes de que
         * BinaryFileResponse llame a readfile(), vaciamos esa pila para
         * que el xlsx llegue al socket sin ningun byte previo.
         */
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download($tmp, $nombre, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma'        => 'public',
        ])->deleteFileAfterSend(true);
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
