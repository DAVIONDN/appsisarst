<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modelo\Personal;
use App\Modelo\Rol;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HU-03 - Consulta del Padron de Personal. Casos CP-13 a CP-19.
 */
class PadronConsultaTest extends TestCase
{
    /** CP-13 - CA-HU03-01 */
    public function test_muestra_el_listado_del_padron(): void
    {
        $personal = Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_ACTIVO,
        ]);

        $this->actingAs($this->adminRrhh())
            ->get('/personal')
            ->assertOk()
            ->assertSee($personal->dni);
    }

    /** CP-14 - CA-HU03-02: filtro por area */
    public function test_filtra_por_area(): void
    {
        $areas = DB::table('area')->orderBy('area_id')->limit(2)->pluck('area_id');
        $this->assertGreaterThanOrEqual(2, $areas->count(), 'Se requieren al menos dos areas.');

        $enArea1 = Personal::create($this->datosPersonalValidos(['area_id' => $areas[0]])
            + ['estado' => Personal::ESTADO_ACTIVO]);
        $enArea2 = Personal::create($this->datosPersonalValidos(['area_id' => $areas[1]])
            + ['estado' => Personal::ESTADO_ACTIVO]);

        $this->actingAs($this->adminRrhh())
            ->get('/personal?area_id='.$areas[0])
            ->assertOk()
            ->assertSee($enArea1->dni)
            ->assertDontSee($enArea2->dni);
    }

    /** CP-15 - CA-HU03-02: filtro por condicion laboral */
    public function test_filtra_por_condicion_laboral(): void
    {
        $cas = Personal::create($this->datosPersonalValidos(['condicion_laboral' => 'CAS'])
            + ['estado' => Personal::ESTADO_ACTIVO]);
        $nombrado = Personal::create($this->datosPersonalValidos(['condicion_laboral' => 'NOMBRADO'])
            + ['estado' => Personal::ESTADO_ACTIVO]);

        $this->actingAs($this->adminRrhh())
            ->get('/personal?condicion_laboral=CAS')
            ->assertSee($cas->dni)
            ->assertDontSee($nombrado->dni);
    }

    /** CP-16 - CA-HU03-02: filtro parcial por cargo */
    public function test_filtra_por_cargo_de_forma_parcial(): void
    {
        $enfermero = Personal::create($this->datosPersonalValidos(['cargo' => 'Enfermero'])
            + ['estado' => Personal::ESTADO_ACTIVO]);
        $chofer = Personal::create($this->datosPersonalValidos(['cargo' => 'Chofer'])
            + ['estado' => Personal::ESTADO_ACTIVO]);

        $this->actingAs($this->adminRrhh())
            ->get('/personal?cargo=Enfermer')
            ->assertSee($enfermero->dni)
            ->assertDontSee($chofer->dni);
    }

    /** CP-18 - busqueda libre por DNI */
    public function test_busca_por_dni(): void
    {
        $buscado = Personal::create($this->datosPersonalValidos() + ['estado' => Personal::ESTADO_ACTIVO]);
        $otro = Personal::create($this->datosPersonalValidos() + ['estado' => Personal::ESTADO_ACTIVO]);

        $this->actingAs($this->adminRrhh())
            ->get('/personal?buscar='.$buscado->dni)
            ->assertSee($buscado->dni)
            ->assertDontSee($otro->dni);
    }

    /** Por defecto el listado muestra solo activos. */
    public function test_el_listado_muestra_solo_activos_por_defecto(): void
    {
        $inactivo = Personal::create($this->datosPersonalValidos() + [
            'estado' => Personal::ESTADO_INACTIVO,
            'motivo_baja' => 'Renuncia voluntaria del trabajador',
        ]);

        $this->actingAs($this->adminRrhh())
            ->get('/personal')
            ->assertDontSee($inactivo->dni);

        $this->actingAs($this->adminRrhh())
            ->get('/personal?estado=INACTIVO')
            ->assertSee($inactivo->dni);
    }

    /** CP-19 - CA-HU03-03: el jefe de area solo ve su area */
    public function test_el_jefe_de_area_solo_ve_al_personal_de_su_area(): void
    {
        $areas = DB::table('area')->orderBy('area_id')->limit(2)->pluck('area_id');

        $jefe = $this->usuarioConRol(Rol::JEFE_AREA, (int) $areas[0]);

        $suyo = Personal::create($this->datosPersonalValidos(['area_id' => $areas[0]])
            + ['estado' => Personal::ESTADO_ACTIVO]);
        $ajeno = Personal::create($this->datosPersonalValidos(['area_id' => $areas[1]])
            + ['estado' => Personal::ESTADO_ACTIVO]);

        $this->actingAs($jefe)
            ->get('/personal')
            ->assertOk()
            ->assertSee($suyo->dni)
            ->assertDontSee($ajeno->dni);
    }
}
