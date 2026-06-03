<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Modelo\Excepciones\ReglaNegocioException;
use App\Controlador\Validaciones\DesactivarPersonalRequest;
use App\Controlador\Validaciones\PersonalRequest;
use App\Modelo\Area;
use App\Modelo\Establecimiento;
use App\Modelo\Horario;
use App\Modelo\LogAuditoria;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use App\Modelo\Servicios\PadronService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Modulo del Padron de Personal (Sprint 1).
 *
 * Coordina las cinco historias del sprint y no contiene reglas de negocio:
 * la validacion vive en los FormRequest y las reglas en PadronService.
 *
 *   HU-01  RF-01  registrar personal            -> create()  / store()
 *   HU-02  RF-02  editar datos del personal     -> edit()    / update()
 *   HU-03  RF-03  consultar el padron           -> index()
 *   HU-04  RF-04  desactivar personal           -> desactivar()
 *   HU-18  RF-20  consultar historial laboral   -> show()
 */
class PersonalController extends Controller
{
    /** Cantidad de filas por pagina (RNF-04: respuesta en menos de 3 s). */
    private const POR_PAGINA = 15;

    public function __construct(private readonly PadronService $padron)
    {
    }

    // -----------------------------------------------------------------
    //  HU-03 - Consulta del padron con filtros
    // -----------------------------------------------------------------

    public function index(Request $request): View
    {
        $filtros = [
            'buscar' => $request->query('buscar'),
            'establecimiento_id' => $request->query('establecimiento_id'),
            'area_id' => $request->query('area_id'),
            'cargo' => $request->query('cargo'),
            'condicion_laboral' => $request->query('condicion_laboral'),
            'estado' => $request->query('estado', Personal::ESTADO_ACTIVO),
        ];

        $personal = $this->consultaBase()
            ->buscar($filtros['buscar'])
            ->deEstablecimiento($filtros['establecimiento_id'])
            ->deArea($filtros['area_id'])
            ->deCargo($filtros['cargo'])
            ->deCondicion($filtros['condicion_laboral'])
            ->deEstado($filtros['estado'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('personal.index', [
            'personal' => $personal,
            'filtros' => $filtros,
            'areas' => $this->areasVisibles(),
            'establecimientos' => Establecimiento::orderBy('nombre')->get(),
            'condiciones' => Personal::CONDICIONES,
            'resumen' => $this->resumen(),
        ]);
    }

    // -----------------------------------------------------------------
    //  HU-01 - Registro de personal
    // -----------------------------------------------------------------

    public function create(): View
    {
        return view('personal.create', $this->datosFormulario());
    }

    public function store(PersonalRequest $request): RedirectResponse
    {
        try {
            $personal = $this->padron->registrar($request->validated());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('personal.index')
            ->with('exito', "Personal {$personal->nombre_completo} registrado correctamente.");
    }

    // -----------------------------------------------------------------
    //  HU-02 - Edicion de datos
    // -----------------------------------------------------------------

    public function edit(Personal $personal): View
    {
        $this->autorizarAcceso($personal);

        return view('personal.edit', $this->datosFormulario() + ['personal' => $personal]);
    }

    public function update(PersonalRequest $request, Personal $personal): RedirectResponse
    {
        $this->autorizarAcceso($personal);

        try {
            $this->padron->actualizar($personal, $request->validated());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('personal.index')
            ->with('exito', "Datos de {$personal->nombre_completo} actualizados correctamente.");
    }

    // -----------------------------------------------------------------
    //  HU-18 - Historial laboral completo
    // -----------------------------------------------------------------

    public function show(Personal $personal): View
    {
        $this->autorizarAcceso($personal);

        $personal->load(['area.establecimiento', 'horario', 'usuario.roles']);

        // El historial reune datos, asistencias y movimientos ordenados del
        // mas reciente al mas antiguo (CA-HU18-01 y CA-HU18-02).
        $asistencias = $personal->asistencias()
            ->orderByDesc('fecha')
            ->limit(50)
            ->get();

        $movimientos = $personal->movimientos()
            ->with(['tipo', 'establecimientoDestino'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $auditoria = LogAuditoria::query()
            ->delRegistro('personal', (int) $personal->personal_id)
            ->with('usuario')
            ->orderByDesc('fecha')
            ->limit(50)
            ->get();

        return view('personal.show', [
            'personal' => $personal,
            'asistencias' => $asistencias,
            'movimientos' => $movimientos,
            'auditoria' => $auditoria,
            'lineaTiempo' => $this->lineaTiempo($asistencias, $movimientos, $personal),
        ]);
    }

    // -----------------------------------------------------------------
    //  HU-04 - Desactivacion (baja logica)
    // -----------------------------------------------------------------

    public function desactivar(DesactivarPersonalRequest $request, Personal $personal): RedirectResponse
    {
        try {
            $this->padron->desactivar($personal, $request->string('motivo_baja')->toString());
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('personal.index')
            ->with('exito', "{$personal->nombre_completo} fue desactivado. Su historial se conserva.");
    }

    public function reactivar(Personal $personal): RedirectResponse
    {
        try {
            $this->padron->reactivar($personal);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('personal.index')
            ->with('exito', "{$personal->nombre_completo} fue reactivado en el padron.");
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /**
     * Consulta base con las relaciones necesarias y el alcance por rol.
     *
     * Carga area + establecimiento en una sola consulta (evita N+1 en el listado)
     * y aplica el filtro de area cuando el usuario es JEFE_AREA (CA-HU03-03).
     *
     * @return Builder<Personal>
     */
    private function consultaBase(): Builder
    {
        return $this->aplicarAlcance(
            Personal::query()->with(['area.establecimiento', 'horario'])
        );
    }

    /**
     * CA-HU03-03 / CA-HU18-03: el Jefe de Area solo ve al personal de su
     * area; los demas roles autorizados ven todo el padron.
     */
    private function aplicarAlcance(Builder $query): Builder
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if ($usuario === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->esJefeDeAreaRestringido($usuario)) {
            return $query->where('personal.area_id', $usuario->areaId());
        }

        return $query;
    }

    private function esJefeDeAreaRestringido(Usuario $usuario): bool
    {
        return $usuario->tieneRol(Rol::JEFE_AREA)
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA, Rol::GERENTE_RED)
            && $usuario->areaId() !== null;
    }

    /** Impide que un jefe de area abra la ficha de personal ajeno a su area. */
    private function autorizarAcceso(Personal $personal): void
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        if ($this->esJefeDeAreaRestringido($usuario) && (int) $personal->area_id !== $usuario->areaId()) {
            abort(403, 'Solo puede consultar al personal de su area.');
        }
    }

    /**
     * Areas que el usuario puede elegir en los filtros y formularios.
     * El JEFE_AREA solo ve su propia area; los demas roles ven todas las areas.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Area>
     */
    private function areasVisibles()
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $query = Area::query()->with('establecimiento')->orderBy('nombre');

        if ($this->esJefeDeAreaRestringido($usuario)) {
            $query->where('area_id', $usuario->areaId());
        }

        return $query->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        return [
            'areas' => $this->areasVisibles(),
            'horarios' => Horario::activos()->orderBy('nombre')->get(),
            'condiciones' => Personal::CONDICIONES,
        ];
    }

    /**
     * Totales del padron que se muestran en las tarjetas del listado (HU-03).
     *
     * Cada contador respeta el alcance por rol (solo el area del JEFE_AREA).
     *
     * @return array{activos: int, inactivos: int, sin_horario: int, areas: int}
     */
    private function resumen(): array
    {
        return [
            // Total de trabajadores con estado ACTIVO dentro del alcance visible
            'activos' => (int) $this->aplicarAlcance(Personal::query())
                ->where('personal.estado', Personal::ESTADO_ACTIVO)->count(),

            // Total de trabajadores con estado INACTIVO (baja logica, HU-04)
            'inactivos' => (int) $this->aplicarAlcance(Personal::query())
                ->where('personal.estado', Personal::ESTADO_INACTIVO)->count(),

            // Activos sin horario asignado (alerta para el Sprint 2 de asistencia)
            'sin_horario' => (int) $this->aplicarAlcance(Personal::query())
                ->where('personal.estado', Personal::ESTADO_ACTIVO)->whereNull('horario_id')->count(),

            // Numero de areas distintas que el usuario puede consultar
            'areas' => $this->areasVisibles()->count(),
        ];
    }

    /**
     * Fusiona asistencias y movimientos en una sola secuencia cronologica
     * descendente, tal como exige el criterio CA-HU18-02.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Modelo\Asistencia>  $asistencias
     * @param  \Illuminate\Support\Collection<int, \App\Modelo\MovimientoInstitucional>  $movimientos
     * @return Collection<int, array<string, mixed>>
     */
    private function lineaTiempo($asistencias, $movimientos, Personal $personal): Collection
    {
        $eventos = collect();

        foreach ($asistencias as $asistencia) {
            $eventos->push([
                'fecha' => $asistencia->fecha,
                'tipo' => 'Asistencia',
                'color' => $asistencia->color,
                'titulo' => $asistencia->estado,
                'detalle' => trim(sprintf(
                    'Entrada: %s | Salida: %s %s',
                    $asistencia->hora_entrada ?? '--:--',
                    $asistencia->hora_salida ?? '--:--',
                    $asistencia->minutos_tardanza > 0 ? "| {$asistencia->minutos_tardanza} min de tardanza" : ''
                )),
            ]);
        }

        foreach ($movimientos as $movimiento) {
            $eventos->push([
                'fecha' => $movimiento->fecha_inicio,
                'tipo' => 'Movimiento',
                'color' => $movimiento->color,
                'titulo' => $movimiento->tipo?->nombre ?? 'Movimiento',
                'detalle' => sprintf(
                    '%s | %s al %s | Estado: %s',
                    $movimiento->motivo,
                    $movimiento->fecha_inicio?->format('d/m/Y'),
                    $movimiento->fecha_fin?->format('d/m/Y'),
                    $movimiento->estado
                ),
            ]);
        }

        $eventos->push([
            'fecha' => $personal->fecha_ingreso,
            'tipo' => 'Padron',
            'color' => 'primary',
            'titulo' => 'Ingreso a la institucion',
            'detalle' => "Cargo: {$personal->cargo} | Condicion: {$personal->condicion_laboral}",
        ]);

        return $eventos->sortByDesc('fecha')->values();
    }
}
