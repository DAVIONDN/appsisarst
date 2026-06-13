<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modelo\Personal;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HU-01 - Registro de Personal. Casos CP-01 a CP-06.
 */
class PadronRegistroTest extends TestCase
{
    /** CA-HU01-01 */
    public function test_el_formulario_de_alta_esta_disponible_para_rrhh(): void
    {
        $this->actingAs($this->adminRrhh())
            ->get('/personal/nuevo')
            ->assertOk()
            ->assertSee('Datos del trabajador');
    }

    /** CP-01 - CA-HU01-04 y CA-HU01-06 */
    public function test_registra_un_trabajador_con_datos_validos(): void
    {
        $datos = $this->datosPersonalValidos();

        $this->actingAs($this->adminRrhh())
            ->post('/personal', $datos)
            ->assertRedirect(route('personal.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('personal', [
            'dni' => $datos['dni'],
            'estado' => Personal::ESTADO_ACTIVO,
        ]);
    }

    /** CP-02 - CA-HU01-02 y CA-HU01-05 */
    public function test_rechaza_el_alta_con_campos_obligatorios_vacios(): void
    {
        $this->actingAs($this->adminRrhh())
            ->post('/personal', [])
            ->assertSessionHasErrors([
                'dni', 'nombres', 'apellidos', 'cargo',
                'area_id', 'condicion_laboral', 'telefono',
                'correo', 'fecha_ingreso',
            ]);
    }

    /** CP-03 - CA-HU01-03 */
    public function test_rechaza_un_dni_duplicado(): void
    {
        $existente = DB::table('personal')->value('dni');

        $this->actingAs($this->adminRrhh())
            ->post('/personal', $this->datosPersonalValidos(['dni' => $existente]))
            ->assertSessionHasErrors('dni');
    }

    /** CP-04 */
    public function test_rechaza_un_dni_que_no_tiene_ocho_digitos(): void
    {
        $this->actingAs($this->adminRrhh())
            ->post('/personal', $this->datosPersonalValidos(['dni' => '1234']))
            ->assertSessionHasErrors('dni');
    }

    /** CP-05 - regla del diseno respaldada por el disparador tg_personal_ingreso_ins */
    public function test_rechaza_una_fecha_de_ingreso_futura(): void
    {
        $this->actingAs($this->adminRrhh())
            ->post('/personal', $this->datosPersonalValidos([
                'fecha_ingreso' => now()->addMonth()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('fecha_ingreso');
    }

    /** CP-06 */
    public function test_rechaza_un_correo_mal_formado(): void
    {
        $this->actingAs($this->adminRrhh())
            ->post('/personal', $this->datosPersonalValidos(['correo' => 'correo-invalido']))
            ->assertSessionHasErrors('correo');
    }

    /** CP-39 - RNF-10 */
    public function test_el_alta_queda_registrada_en_la_auditoria(): void
    {
        $datos = $this->datosPersonalValidos();
        $usuario = $this->adminRrhh();

        $this->actingAs($usuario)->post('/personal', $datos);

        $personalId = DB::table('personal')->where('dni', $datos['dni'])->value('personal_id');

        $this->assertDatabaseHas('log_auditoria', [
            'entidad' => 'personal',
            'registro_id' => $personalId,
            'accion' => 'REGISTRAR_PERSONAL',
            'usuario_id' => $usuario->usuario_id,
        ]);
    }

    /** CA-HU03-03: un jefe de area no puede registrar personal. */
    public function test_un_jefe_de_area_no_puede_registrar_personal(): void
    {
        $jefe = $this->usuarioConRol(\App\Modelo\Rol::JEFE_AREA);

        $this->actingAs($jefe)->get('/personal/nuevo')->assertForbidden();
        $this->actingAs($jefe)->post('/personal', $this->datosPersonalValidos())->assertForbidden();
    }
}
