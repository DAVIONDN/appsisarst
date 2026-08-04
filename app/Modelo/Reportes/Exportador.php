<?php

declare(strict_types=1);

namespace App\Modelo\Reportes;

use Symfony\Component\HttpFoundation\Response;

/**
 * Capa MODELO - Contrato de exportacion (RF-18).
 *
 * Es la interfaz IExportador de la Figura 5.5 del documento de diseno,
 * que aplica el patron Estrategia: cada formato tiene su implementacion y
 * la clase Reporte no necesita conocerlas.
 *
 * Agregar un formato nuevo -CSV, por ejemplo- consiste en escribir una
 * clase que implemente esta interfaz y registrarla en el mapa de
 * ExportacionService. No hay que modificar ningun generador de reportes
 * ni ningun controlador.
 */
interface Exportador
{
    /** Clave del formato tal como viaja en la peticion: PDF, EXCEL. */
    public function formato(): string;

    /** Extension del archivo generado, sin el punto. */
    public function extension(): string;

    /** Etiqueta para los botones de la interfaz. */
    public function etiqueta(): string;

    /** Icono de Bootstrap Icons para el boton. */
    public function icono(): string;

    /** Genera el archivo y lo devuelve como descarga. */
    public function exportar(ResultadoReporte $reporte): Response;
}
