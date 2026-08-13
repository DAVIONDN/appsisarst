<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use App\Modelo\Rol;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HU-04 - Desactivacion de Personal. Casos CP-25 a CP-29.
 */
class PadronDesactivacionTest extends TestCase
{
    private function personalActivo(): Personal
    {
        return Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_ACTIVO,
        ]);
    }

    /** CP-25 - CA-HU04-03: la baja es logica, nunca fisica */
    public function test_la_baja_es_logica_y_conserva_el_registro(): void
    {
        $personal = $this->personalActivo();

        $this->actingAs($this->adminRrhh())
            ->patch("/personal/{$personal->personal_id}/baja", [
                'motivo_baja' => 'Renuncia voluntaria con fecha 30/06/2026',
            ])
            ->assertRedirect(route('personal.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('personal', [
            'personal_id' => $personal->personal_id,
            'estado' => Personal::ESTADO_INACTIVO,
            'motivo_baja' => 'Renuncia voluntaria con fecha 30/06/2026',
        ]);
    }

    public function test_exige_un_motivo_de_baja(): void
    {
        $personal = $this->personalActivo();

        $this->actingAs($this->adminRrhh())
            ->patch("/personal/{$personal->personal_id}/baja", ['motivo_baja' => ''])
            ->assertSessionHasErrors('motivo_baja');

        $this->assertDatabaseHas('personal', [
            'personal_id' => $personal->personal_id,
            'estado' => Personal::ESTADO_ACTIVO,
        ]);
    }

    /** CP-27 - regla del diagrama DS-HU04 */
    public function test_no_permite_dar_de_baja_con_movimientos_vigentes(): void
    {
        $personal = $this->personalActivo();

        MovimientoInstitucional::create([
            'personal_id' => $personal->personal_id,
            'tipo_movimiento_id' => (int) DB::table('tipo_movimiento')->value('tipo_movimiento_id'),
            'establecimiento_destino_id' => null,
            'fecha_inicio' => now()->addDays(3)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(6)->format('Y-m-d'),
            'motivo' => 'Comision de servicio programada',
            'estado' => MovimientoInstitucional::PENDIENTE,
        ]);

        $this->actingAs($this->adminRrhh())
            ->patch("/personal/{$personal->personal_id}/baja", [
                'motivo_baja' => 'Cese por termino de contrato',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('personal', [
            'personal_id' => $personal->personal_id,
            'estado' => Personal::ESTADO_ACTIVO,
        ]);
    }

    /** CP-28 - CA-HU03-03: el jefe de area no puede dar de baja */
    public function test_un_jefe_de_area_no_puede_dar_de_baja(): void
    {
        $personal = $this->personalActivo();
        $jefe = $this->usuarioConRol(Rol::JEFE_AREA, (int) $personal->area_id);

        $this->actingAs($jefe)
            ->patch("/personal/{$personal->personal_id}/baja", [
                'motivo_baja' => 'Intento no autorizado de baja',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', 'No tiene permisos para acceder a esa seccion (PADRON / ELIMINAR).');
    }

    /** CP-29 - operacion inversa */
    public function test_permite_reactivar_a_un_trabajador_inactivo(): void
    {
        $personal = Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_INACTIVO,
            'motivo_baja' => 'Baja registrada por error del operador',
        ]);

        $this->actingAs($this->adminRrhh())
            ->patch("/personal/{$personal->personal_id}/alta")
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('personal', [
            'personal_id' => $personal->personal_id,
            'estado' => Personal::ESTADO_ACTIVO,
            'motivo_baja' => null,
        ]);
    }

    /** CP-41 - RNF-10 */
    public function test_la_baja_queda_registrada_en_la_auditoria(): void
    {
        $personal = $this->personalActivo();
        $usuario = $this->adminRrhh();

        $this->actingAs($usuario)->patch("/personal/{$personal->personal_id}/baja", [
            'motivo_baja' => 'Cese por termino de contrato CAS',
        ]);

        $this->assertDatabaseHas('log_auditoria', [
            'entidad' => 'personal',
            'registro_id' => $personal->personal_id,
            'accion' => 'DESACTIVAR_PERSONAL',
            'usuario_id' => $usuario->usuario_id,
        ]);
    }
}
