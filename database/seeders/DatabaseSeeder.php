<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * SISARST - Sembrado de datos.
 *
 * Este seeder queda intencionalmente vacio.
 *
 * Los catalogos maestros (roles, permisos, tipos de movimiento y horarios),
 * los datos de ejemplo y las cuentas de acceso se cargan con los scripts SQL
 * del documento de diseno, que son la fuente unica de verdad del esquema:
 *
 *   docs/04_BASE_DATOS/01_crear_base_datos_MYSQL.sql
 *   docs/04_BASE_DATOS/02_datos_iniciales_MYSQL.sql
 *   docs/04_BASE_DATOS/03_usuarios_demo_MYSQL.sql
 *
 * Duplicar esa carga aqui abriria la puerta a que el esquema real y el
 * sembrado de Laravel se desincronicen.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('SISARST no usa seeders de Laravel.');
        $this->command?->line('Cargue los datos con los scripts de docs/04_BASE_DATOS/.');
    }
}
