<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modelo\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RF-13 - Acceso al sistema. Casos CP-34 a CP-38 del plan de pruebas.
 */
class AutenticacionTest extends TestCase
{
    public function test_la_pantalla_de_acceso_esta_disponible(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('SISARST');
    }

    /** CP-34 */
    public function test_un_usuario_activo_puede_iniciar_sesion(): void
    {
        $usuario = $this->adminRrhh();

        $this->post('/login', [
            'username' => $usuario->username,
            'password' => 'Clave2026$',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario->fresh());
    }

    /** CP-35 */
    public function test_no_se_puede_ingresar_con_una_clave_incorrecta(): void
    {
        $usuario = $this->adminRrhh();

        $this->from('/login')
            ->post('/login', [
                'username' => $usuario->username,
                'password' => 'clave-equivocada',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_una_cuenta_inactiva_no_puede_ingresar(): void
    {
        $usuario = $this->adminRrhh();
        $usuario->forceFill(['estado' => Usuario::ESTADO_INACTIVO])->save();

        $this->from('/login')
            ->post('/login', [
                'username' => $usuario->username,
                'password' => 'Clave2026$',
            ])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    /** CP-36 */
    public function test_las_rutas_protegidas_exigen_sesion(): void
    {
        $this->get('/personal')->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    /** CP-37 */
    public function test_el_usuario_puede_cerrar_sesion(): void
    {
        $usuario = $this->adminRrhh();

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** CP-38 - RNF-10 */
    public function test_el_ingreso_queda_registrado_en_la_auditoria(): void
    {
        $usuario = $this->adminRrhh();

        $this->post('/login', [
            'username' => $usuario->username,
            'password' => 'Clave2026$',
        ]);

        $existe = DB::table('log_auditoria')
            ->where('usuario_id', $usuario->usuario_id)
            ->where('accion', 'INICIAR_SESION')
            ->exists();

        $this->assertTrue($existe, 'No se registro el ingreso en el log de auditoria.');
    }
}
