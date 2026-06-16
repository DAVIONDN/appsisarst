<?php

declare(strict_types=1);

namespace App\Controlador\Consola;

use App\Modelo\Servicios\CierreJornadaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Capa CONTROLADOR (consola) - Cierre de jornada, HU-07 / RF-07.
 *
 * Es el punto de entrada del actor "Sistema" del diagrama de casos de uso:
 * la tarea programada que al terminar el dia genera las faltas de quien no
 * marco entrada (CA-HU07-02), respetando las licencias y comisiones
 * aprobadas, que se registran como JUSTIFICADO.
 *
 * Igual que un controlador web, aqui no hay reglas de negocio: solo recoge
 * los parametros y delega en CierreJornadaService.
 *
 * Uso manual:
 *   php artisan sisarst:cerrar-jornada
 *   php artisan sisarst:cerrar-jornada --fecha=2026-08-07
 *   php artisan sisarst:cerrar-jornada --simular
 *
 * Programado: ver routes/console.php (todos los dias a las 23:30).
 */
class CerrarJornadaCommand extends Command
{
    protected $signature = 'sisarst:cerrar-jornada
                            {--fecha= : Fecha a procesar en formato AAAA-MM-DD (por defecto, hoy)}
                            {--simular : Muestra lo que haria sin escribir en la base de datos}';

    protected $description = 'Genera las faltas y los justificados de la jornada (HU-07 / RF-07)';

    public function handle(CierreJornadaService $cierre): int
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse((string) $this->option('fecha'))
            : Carbon::today();

        if ($fecha->isFuture()) {
            $this->error('No se puede cerrar una jornada futura.');

            return self::FAILURE;
        }

        $this->info("Cierre de jornada del {$fecha->format('d/m/Y')}");

        if ($this->option('simular')) {
            $this->warn('Modo simulacion: no se escribira nada en la base de datos.');
            $this->line('Ejecute sin --simular para aplicar los cambios.');

            return self::SUCCESS;
        }

        $r = $cierre->procesar($fecha);

        $this->newLine();
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Faltas generadas', $r['faltas']],
                ['Justificados por movimiento aprobado', $r['justificados']],
                ['Omitidos (ya tenian jornada o no era dia laborable)', $r['omitidos']],
                ['Sin horario asignado (no evaluables, HU-16)', $r['sin_horario']],
            ]
        );

        if ($r['sin_horario'] > 0) {
            $this->warn(
                "Atencion: {$r['sin_horario']} trabajador(es) activo(s) no tienen horario asignado "
                .'y quedaron fuera de la evaluacion (CA-HU16-03).'
            );
        }

        $this->info('Cierre completado.');

        return self::SUCCESS;
    }
}
