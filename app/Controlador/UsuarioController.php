<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Controlador\Validaciones\ClaveRequest;
use App\Controlador\Validaciones\UsuarioRequest;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\LogAuditoria;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Servicios\UsuarioService;
use App\Modelo\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Gestion de Usuarios del Sistema (HU-11 y HU-12).
 *
 * No contiene reglas de negocio: la validacion vive en los FormRequest y
 * las reglas en UsuarioService.
 *
 *   CA-HU11-01  crear cuentas                 -> create() / store()
 *   CA-HU11-02  editar cuentas                -> edit() / update()
 *   CA-HU11-03  desactivar sin perder historial -> desactivar()
 *   CA-HU11-04  no duplicar username ni correo -> UsuarioRequest
 *   CA-HU12-01  asignar roles                 -> asignarRoles()
 */
class UsuarioController extends Controller
{
    private const POR_PAGINA = 15;

    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    // -----------------------------------------------------------------
    //  Listado
    // -----------------------------------------------------------------

    public function index(Request $request): View
    {
        $filtros = [
            'buscar' => $request->query('buscar'),
            'estado' => $request->query('estado'),
            'rol_id' => $request->query('rol_id'),
        ];

        $usuarios = Usuario::query()
            ->with(['personal.area', 'roles'])
            ->buscar($filtros['buscar'])
            ->deEstado($filtros['estado'])
            ->deRol($filtros['rol_id'])
            ->orderBy('username')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('usuario.index', [
            'usuarios' => $usuarios,
            'filtros' => $filtros,
            'estados' => Usuario::ESTADOS,
            'roles' => Rol::orderBy('nombre')->get(),
            'resumen' => [
                'activos' => Usuario::where('estado', Usuario::ESTADO_ACTIVO)->count(),
                'inactivos' => Usuario::where('estado', Usuario::ESTADO_INACTIVO)->count(),
                'bloqueados' => Usuario::where('estado', Usuario::ESTADO_BLOQUEADO)->count(),
                'sin_rol' => Usuario::sinRol()->count(),
            ],
            'sinCuenta' => Personal::query()
                ->activo()
                ->whereDoesntHave('usuario')
                ->count(),
        ]);
    }

    /** Ficha de la cuenta con su historial de auditoria. */
    public function show(Usuario $usuario): View
    {
        $usuario->load(['personal.area.establecimiento', 'roles.permisos']);

        return view('usuario.show', [
            'usuario' => $usuario,
            'auditoria' => LogAuditoria::query()
                ->delRegistro('usuario', (int) $usuario->usuario_id)
                ->with('usuario.personal')
                ->orderByDesc('fecha')
                ->limit(60)
                ->get(),
        ]);
    }

    // -----------------------------------------------------------------
    //  CA-HU11-01 - Alta
    // -----------------------------------------------------------------

    public function create(): View
    {
        return view('usuario.create', $this->datosFormulario());
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        try {
            $usuario = $this->usuarios->crear($request->validated(), $request->input('roles', []));
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('usuario.show', $usuario)
            ->with('exito', "Cuenta {$usuario->username} creada correctamente.");
    }

    // -----------------------------------------------------------------
    //  CA-HU11-02 - Edicion
    // -----------------------------------------------------------------

    public function edit(Usuario $usuario): View
    {
        $usuario->load('roles', 'personal.area');

        return view('usuario.edit', $this->datosFormulario($usuario) + ['usuario' => $usuario]);
    }

    public function update(UsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        try {
            $this->usuarios->actualizar($usuario, $request->validated());
            $this->usuarios->asignarRoles($usuario, $request->input('roles', []));
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('usuario.show', $usuario)
            ->with('exito', "Cuenta {$usuario->username} actualizada correctamente.");
    }

    // -----------------------------------------------------------------
    //  CA-HU11-03 - Estado de la cuenta
    // -----------------------------------------------------------------

    public function desactivar(Usuario $usuario): RedirectResponse
    {
        try {
            $this->usuarios->desactivar($usuario);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('usuario.index')
            ->with('exito', "Cuenta {$usuario->username} desactivada. Su historial se conserva.");
    }

    public function reactivar(Usuario $usuario): RedirectResponse
    {
        try {
            $this->usuarios->reactivar($usuario);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('usuario.index')
            ->with('exito', "Cuenta {$usuario->username} reactivada.");
    }

    /** Desbloquea una cuenta bloqueada por intentos fallidos (DS-RF13). */
    public function desbloquear(Usuario $usuario): RedirectResponse
    {
        try {
            $this->usuarios->desbloquear($usuario);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('usuario.show', $usuario)
            ->with('exito', "Cuenta {$usuario->username} desbloqueada.");
    }

    // -----------------------------------------------------------------
    //  Restablecimiento administrativo de la clave
    // -----------------------------------------------------------------

    public function formularioClave(Usuario $usuario): View
    {
        return view('usuario.clave', ['usuario' => $usuario]);
    }

    public function restablecerClave(ClaveRequest $request, Usuario $usuario): RedirectResponse
    {
        try {
            $this->usuarios->restablecerClave($usuario, $request->string('password')->toString());
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('usuario.show', $usuario)
            ->with('exito', "Contrasena de {$usuario->username} restablecida. Comuniquesela por un medio seguro.");
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /**
     * Personal seleccionable y catalogo de roles.
     *
     * Solo el personal activo y sin cuenta puede recibir una nueva. Al
     * editar se agrega el trabajador ya asociado, que de otro modo no
     * aparecería en la lista y el formulario perderia su valor actual.
     *
     * @return array<string, mixed>
     */
    private function datosFormulario(?Usuario $usuario = null): array
    {
        $personal = Personal::query()
            ->activo()
            ->with('area')
            ->whereDoesntHave('usuario')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        if ($usuario?->personal !== null) {
            $personal = $personal
                ->push($usuario->personal)
                ->unique('personal_id')
                ->sortBy(fn (Personal $p) => $p->apellidos.$p->nombres)
                ->values();
        }

        return [
            'personal' => $personal,
            'roles' => Rol::where('activo', true)->orderBy('nombre')->get(),
        ];
    }
}
