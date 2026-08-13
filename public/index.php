<?php

/*
 * Activar output buffering desde el primer byte de la peticion.
 *
 * XAMPP tiene output_buffering=0 en php.ini. Sin buffer, cualquier
 * archivo PHP guardado con BOM UTF-8 (EF BB BF) o cualquier notice/
 * warning con display_errors=1 se imprime directamente al socket
 * ANTES de que la aplicacion pueda enviar sus propios headers o
 * contenido binario (xlsx, pdf). El BOM corrompe las descargas.
 *
 * ob_start() aqui garantiza que toda salida quede retenida en memoria
 * hasta que Response::send() la controle correctamente.
 */
ob_start();

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
