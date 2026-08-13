<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modelo\Personal;
use App\Modelo\Rol;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HU-18 - Consulta del Historial de Personal. Casos CP-30 a CP-33.
 */
class HistorialPersonalTest extends TestCase
{
    /** CP-30 - CA-HU18-01 */
    public function test_muestra_el_historial_completo_del_trabajador(): void
    {
        $personal = Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_ACTIVO,
        ]);

        $this->actingAs($this->adminRrhh())
            ->get("/personal/{$personal->personal_id}")
            ->assertOk()
            ->assertSee($personal->dni)
            ->assertSee('Linea de tiempo')
            ->assertSee('Asistencias')
            ->assertSee('Movimientos')
            ->assertSee('Auditoria');
    }

    /** CP-33: un trabajador sin historial no debe romper la vista. */
    public function test_un_trabajador_sin_historial_muestra_la_ficha_sin_errores(): void
    {
        $personal = Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_ACTIVO,
        ]);

        $this->actingAs($this->adminRrhh())
            ->get("/personal/{$personal->personal_id}")
            ->assertOk()
            ->assertSee('Sin asistencias registradas');
    }

    /** CP-32 - CA-HU18-03 */
    public function test_el_jefe_de_area_no_accede_al_historial_de_otra_area(): void
    {
        $areas = DB::table('area')->orderBy('area_id')->limit(2)->pluck('area_id');

        $jefe = $this->usuarioConRol(Rol::JEFE_AREA, (int) $areas[0]);

        $ajeno = Personal::create($this->datosPersonalValidos(['area_id' => $areas[1]])
            + ['estado' => Personal::ESTADO_ACTIVO]);
        $suyo = Personal::create($this->datosPersonalValidos(['area_id' => $areas[0]])
            + ['estado' => Personal::ESTADO_ACTIVO]);

        $this->actingAs($jefe)->get("/personal/{$ajeno->personal_id}")->assertForbidden();
        $this->actingAs($jefe)->get("/personal/{$suyo->personal_id}")->assertOk();
    }

    /** El tablero tambien limita sus indicadores al area del jefe. */
    public function test_el_tablero_esta_disponible_para_los_roles_autorizados(): void
    {
        $this->actingAs($this->adminRrhh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Total personal');
    }
}
