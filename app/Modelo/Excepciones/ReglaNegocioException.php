<?php

declare(strict_types=1);

namespace App\Modelo\Excepciones;

use RuntimeException;

/**
 * Excepcion de regla de negocio incumplida.
 *
 * La lanzan los servicios de dominio (capa Modelo) cuando una operacion
 * viola una regla del analisis; el controlador la traduce a un mensaje
 * de error en la vista, sin exponer detalles tecnicos al usuario.
 */
class ReglaNegocioException extends RuntimeException
{
}
