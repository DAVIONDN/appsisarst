<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Controlador\Validaciones\CambiarEstadoRequest;
use App\Controlador\Validaciones\MovimientoRequest;
use App\Modelo\Area;
use App\Modelo\Establecimiento;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\LogAuditoria;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Servicios\MovimientoService;
use App\Modelo\TipoMovimiento;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Movimientos Institucionales (Sprint 3).
 *
 * No contiene reglas de negocio: la validacion vive en los FormRequest y
 * las reglas en MovimientoService.
 *
 *   HU-08  RF-08  registrar movimiento          -> create() / store()
 *   HU-09  RF-09  consultar movimientos         -> index() / show()
 *   HU-10  RF-10  actualizar el estado          -> cambiarEstado()
 */
class MovimientoController extends Controller
{
    /** Filas por pagina (RNF-04). */
    private const POR_PAGINA = 15;

    public function __construct(private readonly MovimientoService $movimientos)
    {
    }

    // -----------------------------------------------------------------
    //  HU-09 - Consulta de movimientos
    // -----------------------------------------------------------------

    public function index(Request $request): View
    {
        $filtros = [
            'buscar' => $request->query('buscar'),
            'personal_id' => $request->query('personal_id'),
            'tipo_movimiento_id' => $request->query('tipo_movimiento_id'),
            'estado' => $request->query('estado'),
            'area_id' => $request->query('area_id'),
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
        ];

        $consulta = fn (): Builder => $this->aplicarAlcance(MovimientoInstitucional::query())
            ->buscar($filtros['buscar'])
            ->dePersonal($filtros['personal_id'])
            ->deTipo($filtros['tipo_movimiento_id'])
            ->deEstado($filtros['estado'])
            ->deArea($filtros['area_id'])
            ->enPeriodo($filtros['desde'], $filtros['hasta']);

        // CA-HU09-02: del mas reciente al mas antiguo.
        $movimientos = $consulta()
            ->with(['personal.area', 'tipo', 'establecimientoDestino'])
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('movimiento_id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        // CA-HU09-03: totales por estado sobre todo el filtro, no la pagina.
        $porEstado = $consulta()
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('movimiento.index', [
            'movimientos' => $movimientos,
            'filtros' => $filtros,
            'porEstado' => $porEstado,
            'estados' => MovimientoInstitucional::ESTADOS,
            'tipos' => TipoMovimiento::where('activo', true)->orderBy('nombre')->get(),
            'areas' => $this->areasVisibles(),
            'personal' => $this->personalVisible(),
            'vencidos' => $this->aplicarAlcance(MovimientoInstitucional::query())
                ->where('estado', MovimientoInstitucional::APROBADO)
                ->whereDate('fecha_fin', '<', now()->toDateString())
                ->count(),
        ]);
    }

    /** Detalle del movimiento con su historial de cambios (CA-HU09-02). */
    public function show(MovimientoInstitucional $movimiento): View
    {
        $movimiento->load(['personal.area.establecimiento', 'tipo', 'establecimientoDestino']);
        $this->autorizarAcceso($movimiento);

        return view('movimiento.show', [
            'movimiento' => $movimiento,
            'auditoria' => LogAuditoria::query()
                ->delRegistro('movimiento_institucional', (int) $movimiento->movimiento_id)
                ->with('usuario.personal')
                ->orderByDesc('fecha')
                ->get(),
            'areasDestino' => $this->areasDelDestino($movimiento),
        ]);
    }

    // -----------------------------------------------------------------
    //  HU-08 - Registro
    // -----------------------------------------------------------------

    public function create(): View
    {
        return view('movimiento.create', $this->datosFormulario());
    }

    public function store(MovimientoRequest $request): RedirectResponse
    {
        try {
            $movimiento = $this->movimientos->registrar($request->validated());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('movimiento.show', $movimiento)
            ->with('exito', 'Movimiento registrado en estado PENDIENTE.');
    }

    public function edit(MovimientoInstitucional $movimiento): View
    {
        $this->autorizarAcceso($movimiento);

        if (! $movimiento->es_editable) {
            abort(403, "Solo se pueden editar los movimientos PENDIENTES. Este esta {$movimiento->estado}.");
        }

        return view('movimiento.edit', $this->datosFormulario() + ['movimiento' => $movimiento]);
    }

    public function update(MovimientoRequest $request, MovimientoInstitucional $movimiento): RedirectResponse
    {
        $this->autorizarAcceso($movimiento);

        try {
            $this->movimientos->actualizar($movimiento, $request->validated());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('movimiento.show', $movimiento)
            ->with('exito', 'Movimiento actualizado correctamente.');
    }

    // -----------------------------------------------------------------
    //  HU-10 - Actualizacion del estado
    // -----------------------------------------------------------------

    public function cambiarEstado(CambiarEstadoRequest $request, MovimientoInstitucional $movimiento): RedirectResponse
    {
        $movimiento->load(['tipo', 'personal', 'establecimientoDestino']);
        $this->autorizarAcceso($movimiento);

        $nuevoEstado = $request->string('estado')->toString();

        try {
            $this->movimientos->cambiarEstado(
                $movimiento,
                $nuevoEstado,
                $request->input('motivo_rechazo'),
                $request->filled('area_destino_id') ? $request->integer('area_destino_id') : null
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('movimiento.show', $movimiento)
            ->with('exito', "El movimiento paso a estado {$nuevoEstado}.");
    }

    /** Cierra en bloque los aprobados cuyo periodo ya termino. */
    public function finalizarVencidos(): RedirectResponse
    {
        try {
            $total = $this->movimientos->finalizarVencidos();
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('movimiento.index')
            ->with('exito', $total === 0
                ? 'No habia movimientos aprobados con periodo vencido.'
                : "Se finalizaron {$total} movimiento(s) con periodo vencido.");
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /**
     * CA-HU09: el Jefe de Area consulta los movimientos de su personal.
     * El filtro se aplica en la consulta, no en la vista.
     */
    private function aplicarAlcance(Builder $query): Builder
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if ($usuario === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->esJefeDeAreaRestringido($usuario)) {
            return $query->whereHas(
                'personal',
                fn (Builder $p) => $p->where('area_id', $usuario->areaId())
            );
        }

        return $query;
    }

    private function esJefeDeAreaRestringido(Usuario $usuario): bool
    {
        return $usuario->tieneRol(Rol::JEFE_AREA)
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA, Rol::GERENTE_RED)
            && $usuario->areaId() !== null;
    }

    private function autorizarAcceso(MovimientoInstitucional $movimiento): void
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        if ($this->esJefeDeAreaRestringido($usuario)
            && (int) $movimiento->personal?->area_id !== $usuario->areaId()) {
            abort(403, 'Solo puede consultar los movimientos del personal de su area.');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Personal>
     */
    private function personalVisible()
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        return Personal::query()
            ->activo()
            ->with('area')
            ->when(
                $this->esJefeDeAreaRestringido($usuario),
                fn (Builder $q) => $q->where('area_id', $usuario->areaId())
            )
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Area>
     */
    private function areasVisibles()
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        return Area::query()
            ->with('establecimiento')
            ->when(
                $this->esJefeDeAreaRestringido($usuario),
                fn (Builder $q) => $q->where('area_id', $usuario->areaId())
            )
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Areas del establecimiento de destino, necesarias al aprobar una
     * rotacion (DS-HU10: efecto colateral sobre el padron).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Area>|null
     */
    private function areasDelDestino(MovimientoInstitucional $movimiento)
    {
        if ($movimiento->tipo?->nombre !== MovimientoInstitucional::TIPO_ROTACION
            || $movimiento->establecimiento_destino_id === null) {
            return null;
        }

        return Area::where('establecimiento_id', $movimiento->establecimiento_destino_id)
            ->activas()
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        return [
            'personal' => $this->personalVisible(),
            'tipos' => TipoMovimiento::where('activo', true)->orderBy('nombre')->get(),
            'establecimientos' => Establecimiento::where('activo', true)->orderBy('nombre')->get(),
        ];
    }
}
