<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Controlador\Validaciones\PermisosRolRequest;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\LogAuditoria;
use App\Modelo\Rol;
use App\Modelo\Servicios\RolPermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Roles y permisos (HU-12 / RF-12).
 *
 *   CA-HU12-01  asignar roles a usuarios   -> UsuarioController
 *   CA-HU12-02  aplicar los permisos       -> middleware VerificarPermiso
 *   CA-HU12-03  configurar por modulo y accion -> edit() / update()
 *   CA-HU12-04  registrar los cambios      -> RolPermisoService + auditoria
 */
class RolController extends Controller
{
    public function __construct(private readonly RolPermisoService $rolesPermisos)
    {
    }

    public function index(): View
    {
        return view('rol.index', [
            'roles' => Rol::query()
                ->with('permisos')
                ->withCount('usuarios')
                ->orderBy('nombre')
                ->get(),
            'matriz' => $this->rolesPermisos->matriz(),
            'acciones' => $this->rolesPermisos->acciones(),
        ]);
    }

    /** CA-HU12-03: grilla de modulo x accion para configurar el rol. */
    public function edit(Rol $rol): View
    {
        $rol->load('permisos');

        return view('rol.edit', [
            'rol' => $rol,
            'matriz' => $this->rolesPermisos->matriz(),
            'acciones' => $this->rolesPermisos->acciones(),
            'asignados' => $rol->permisos->pluck('permiso_id')->all(),
            'auditoria' => LogAuditoria::query()
                ->delRegistro('rol', (int) $rol->rol_id)
                ->with('usuario.personal')
                ->orderByDesc('fecha')
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(PermisosRolRequest $request, Rol $rol): RedirectResponse
    {
        try {
            $this->rolesPermisos->sincronizarPermisos($rol, $request->permisoIds());
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('rol.index')
            ->with('exito', "Permisos del rol {$rol->nombre} actualizados.");
    }
}
