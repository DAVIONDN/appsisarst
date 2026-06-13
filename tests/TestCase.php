<?php

declare(strict_types=1);

namespace Tests;

use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Base de las pruebas del Sprint 1.
 *
 * Las pruebas corren contra la base de datos "red_salud_tayacaja_test",
 * creada con los mismos scripts SQL que la base real, para que los
 * disparadores y las restricciones CHECK tambien queden verificados.
 * Cada prueba se ejecuta dentro de una transaccion que se revierte al
 * terminar, de modo que los datos de partida no se alteran.
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        parent::tearDown();
    }

    /** Crea un usuario de prueba con el rol indicado y devuelve el modelo. */
    protected function usuarioConRol(string $nombreRol, ?int $areaId = null): Usuario
    {
        $personal = Personal::create([
            'area_id' => $areaId ?? (int) DB::table('area')->value('area_id'),
            'horario_id' => null,
            'dni' => (string) random_int(10000000, 99999999),
            'nombres' => 'Usuario',
            'apellidos' => 'De Prueba',
            'cargo' => 'Analista',
            'condicion_laboral' => 'CAS',
            'telefono' => '900000000',
            'correo' => 'prueba'.random_int(1000, 9999).'@redsaludtayacaja.gob.pe',
            'fecha_ingreso' => '2020-01-15',
            'estado' => Personal::ESTADO_ACTIVO,
        ]);

        $usuario = Usuario::create([
            'personal_id' => $personal->personal_id,
            'username' => 'test'.random_int(1000, 9999),
            'password_hash' => Hash::make('Clave2026$'),
            'correo_institucional' => $personal->correo,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        $rol = Rol::where('nombre', $nombreRol)->firstOrFail();
        $usuario->roles()->attach($rol->rol_id);

        return $usuario->fresh(['roles.permisos', 'personal']);
    }

    /** Atajo: administrador de RRHH, que tiene todos los permisos del padron. */
    protected function adminRrhh(): Usuario
    {
        return $this->usuarioConRol(Rol::ADMIN_RRHH);
    }

    /**
     * Datos validos para el formulario del padron.
     *
     * @return array<string, mixed>
     */
    protected function datosPersonalValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'dni' => (string) random_int(10000000, 99999999),
            'nombres' => 'Ana Maria',
            'apellidos' => 'Lopez Vargas',
            'cargo' => 'Tecnico Administrativo',
            'area_id' => (int) DB::table('area')->value('area_id'),
            'horario_id' => null,
            'condicion_laboral' => 'CAS',
            'telefono' => '987654321',
            'correo' => 'alopez'.random_int(100, 999).'@redsaludtayacaja.gob.pe',
            'fecha_ingreso' => '2024-03-01',
        ], $sobrescribir);
    }
}
