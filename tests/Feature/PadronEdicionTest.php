<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modelo\Personal;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HU-02 - Edicion de Datos del Personal. Casos CP-08 a CP-12.
 */
class PadronEdicionTest extends TestCase
{
    private function personalDePrueba(): Personal
    {
        return Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_ACTIVO,
        ]);
    }

    /** CP-08 - CA-HU02-01 y CA-HU02-02 */
    public function test_el_formulario_muestra_los_datos_actuales(): void
    {
        $personal = $this->personalDePrueba();

        $this->actingAs($this->adminRrhh())
            ->get("/personal/{$personal->personal_id}/editar")
            ->assertOk()
            ->assertSee($personal->dni)
            ->assertSee($personal->cargo);
    }

    /** CP-09 - CA-HU02-04 y CA-HU02-05 */
    public function test_actualiza_los_datos_del_trabajador(): void
    {
        $personal = $this->personalDePrueba();

        $datos = $this->datosPersonalValidos([
            'dni' => $personal->dni,
            'cargo' => 'Coordinador de Enfermeria',
            'correo' => $personal->correo,
        ]);

        $this->actingAs($this->adminRrhh())
            ->put("/personal/{$personal->personal_id}", $datos)
            ->assertRedirect(route('personal.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('personal', [
            'personal_id' => $personal->personal_id,
            'cargo' => 'Coordinador de Enfermeria',
        ]);
    }

    /** CP-10 - CA-HU02-03 */
    public function test_rechaza_el_dni_de_otro_trabajador(): void
    {
        $personal = $this->personalDePrueba();
        $otroDni = DB::table('personal')
            ->where('personal_id', '<>', $personal->personal_id)
            ->value('dni');

        $this->actingAs($this->adminRrhh())
            ->put("/personal/{$personal->personal_id}", $this->datosPersonalValidos([
                'dni' => $otroDni,
            ]))
            ->assertSessionHasErrors('dni');
    }

    /** CP-11: conservar el propio DNI no debe disparar el error de unicidad. */
    public function test_permite_guardar_conservando_su_propio_dni(): void
    {
        $personal = $this->personalDePrueba();

        $this->actingAs($this->adminRrhh())
            ->put("/personal/{$personal->personal_id}", $this->datosPersonalValidos([
                'dni' => $personal->dni,
            ]))
            ->assertSessionHasNoErrors();
    }

    /** CP-12: la edicion nunca cambia el estado del trabajador. */
    public function test_la_edicion_no_modifica_el_estado(): void
    {
        $personal = $this->personalDePrueba();

        $this->actingAs($this->adminRrhh())
            ->put("/personal/{$personal->personal_id}", $this->datosPersonalValidos([
                'dni' => $personal->dni,
                'estado' => Personal::ESTADO_INACTIVO,
                'motivo_baja' => 'intento de baja encubierta',
            ]));

        $this->assertDatabaseHas('personal', [
            'personal_id' => $personal->personal_id,
            'estado' => Personal::ESTADO_ACTIVO,
            'motivo_baja' => null,
        ]);
    }

    /** CP-40 - RNF-10 */
    public function test_la_edicion_queda_registrada_en_la_auditoria(): void
    {
        $personal = $this->personalDePrueba();
        $usuario = $this->adminRrhh();

        $this->actingAs($usuario)->put("/personal/{$personal->personal_id}", $this->datosPersonalValidos([
            'dni' => $personal->dni,
            'cargo' => 'Nuevo Cargo',
        ]));

        $this->assertDatabaseHas('log_auditoria', [
            'entidad' => 'personal',
            'registro_id' => $personal->personal_id,
            'accion' => 'EDITAR_PERSONAL',
            'usuario_id' => $usuario->usuario_id,
        ]);
    }
}
