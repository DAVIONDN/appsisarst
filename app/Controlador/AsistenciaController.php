<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Controlador\Validaciones\CorregirAsistenciaRequest;
use App\Controlador\Validaciones\MarcacionRequest;
use App\Modelo\Area;
use App\Modelo\Asistencia;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Servicios\AsistenciaService;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Modulo de Control de Asistencia (Sprint 2).
 *
 * No contiene reglas de negocio: la validacion vive en los FormRequest y
 * las reglas en AsistenciaService.
 *
 *   HU-05  RF-05  registrar entrada y salida     -> create() / store()
 *   HU-06  RF-06  consultar por periodo          -> index()
 *   HU-07  RF-07  tardanzas (al marcar)          -> AsistenciaService
 *          RF-07  faltas (cierre de jornada)     -> CierreJornadaService
 */
class AsistenciaController extends Controller
{
    /** Filas por pagina (RNF-04: respuesta en menos de 3 s). */
    private const POR_PAGINA = 20;

    public function __construct(private readonly AsistenciaService $asistencia)
    {
    }

    // -----------------------------------------------------------------
    //  HU-06 - Consulta de asistencia por periodo
    // -----------------------------------------------------------------

    public function index(Request $request): View
    {
        $filtros = [
            'desde' => $request->query('desde', now()->startOfMonth()->toDateString()),
            'hasta' => $request->query('hasta', now()->toDateString()),
            'personal_id' => $request->query('personal_id'),
            'area_id' => $request->query('area_id'),
            'cargo' => $request->query('cargo'),
            'estado' => $request->query('estado'),
        ];

        $consulta = fn (): Builder => $this->aplicarAlcance(Asistencia::query())
            ->entreFechas($filtros['desde'], $filtros['hasta'])
            ->dePersonal($filtros['personal_id'])
            ->deArea($filtros['area_id'])
            ->deCargo($filtros['cargo'])
            ->deEstado($filtros['estado']);

        $registros = $consulta()
            ->with(['personal.area', 'personal.horario'])
            ->orderByDesc('fecha')
            ->orderBy('personal_id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        // El resumen se calcula sobre TODO el periodo filtrado, no solo
        // sobre la pagina visible (CA-HU06-02).
        $resumen = $this->asistencia->resumen($consulta()->get(['estado', 'minutos_tardanza']));

        return view('asistencia.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'resumen' => $resumen,
            'areas' => $this->areasVisibles(),
            'personal' => $this->personalVisible(),
            'estados' => Asistencia::ESTADOS,
        ]);
    }

    // -----------------------------------------------------------------
    //  HU-05 - Marcacion de entrada y salida
    // -----------------------------------------------------------------

    public function create(Request $request): View
    {
        $fecha = $request->query('fecha', now()->toDateString());

        return view('asistencia.create', [
            'fecha' => $fecha,
            'personal' => $this->personalVisible(),
            'jornadasDelDia' => $this->aplicarAlcance(Asistencia::query())
                ->whereDate('fecha', $fecha)
                ->with('personal')
                ->orderByDesc('asistencia_id')
                ->get(),
            'sinHorario' => $this->personalVisible()->whereNull('horario_id')->count(),
        ]);
    }

    public function store(MarcacionRequest $request): RedirectResponse
    {
        $personal = Personal::with('horario')->findOrFail($request->integer('personal_id'));
        $this->autorizarAcceso($personal);

        $fecha = $request->string('fecha')->toString();
        $hora = $request->horaCompleta();

        try {
            $asistencia = $request->string('tipo')->toString() === 'ENTRADA'
                ? $this->asistencia->registrarEntrada($personal, $fecha, $hora, $request->input('observacion'))
                : $this->asistencia->registrarSalida($personal, $fecha, $hora);
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('asistencia.create', ['fecha' => $fecha])
            ->with('exito', $this->mensajeDeExito($request->string('tipo')->toString(), $personal, $asistencia));
    }

    // -----------------------------------------------------------------
    //  Correccion manual de una jornada
    // -----------------------------------------------------------------

    public function edit(Asistencia $asistencia): View
    {
        $asistencia->load(['personal.area', 'personal.horario']);
        $this->autorizarAcceso($asistencia->personal);

        return view('asistencia.edit', [
            'asistencia' => $asistencia,
            'estados' => Asistencia::ESTADOS,
        ]);
    }

    public function update(CorregirAsistenciaRequest $request, Asistencia $asistencia): RedirectResponse
    {
        $asistencia->load('personal.horario');
        $this->autorizarAcceso($asistencia->personal);

        try {
            $this->asistencia->corregir($asistencia, $request->datosNormalizados());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('asistencia.index', ['desde' => $asistencia->fecha->toDateString(), 'hasta' => $asistencia->fecha->toDateString()])
            ->with('exito', 'Jornada corregida correctamente.');
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /**
     * CA-HU06-03: el Jefe de Area solo consulta la asistencia del personal
     * de su area. El filtro se aplica en la consulta, no en la vista, de
     * modo que el dato restringido nunca llega al navegador.
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
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA)
            && $usuario->areaId() !== null;
    }

    private function autorizarAcceso(?Personal $personal): void
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        if ($personal === null) {
            abort(404);
        }

        if ($this->esJefeDeAreaRestringido($usuario) && (int) $personal->area_id !== $usuario->areaId()) {
            abort(403, 'Solo puede gestionar la asistencia del personal de su area.');
        }
    }

    /**
     * Personal activo que el usuario puede seleccionar.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Personal>
     */
    private function personalVisible()
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        return Personal::query()
            ->activo()
            ->with(['area', 'horario'])
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

    private function mensajeDeExito(string $tipo, Personal $personal, Asistencia $asistencia): string
    {
        if ($tipo === 'SALIDA') {
            return "Salida de {$personal->nombre_completo} registrada a las {$asistencia->salida_corta}"
                .' ('.$asistencia->horas_trabajadas.' horas trabajadas).';
        }

        $base = "Entrada de {$personal->nombre_completo} registrada a las {$asistencia->entrada_corta}.";

        if ($asistencia->estado === Asistencia::TARDANZA) {
            return $base." Se registro TARDANZA de {$asistencia->minutos_tardanza} minutos.";
        }

        if ($personal->horario === null) {
            return $base.' Atencion: el trabajador no tiene horario asignado, no se evaluo su puntualidad (HU-16).';
        }

        return $base.' Marcacion PUNTUAL.';
    }

    /** Fecha de hoy en el formato que usan los formularios. */
    public static function hoy(): string
    {
        return Carbon::now()->toDateString();
    }
}
