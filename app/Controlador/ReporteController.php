<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Controlador\Validaciones\ReporteRequest;
use App\Modelo\Area;
use App\Modelo\Asistencia;
use App\Modelo\Establecimiento;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use App\Modelo\Reportes\ResultadoReporte;
use App\Modelo\Rol;
use App\Modelo\Servicios\ExportacionService;
use App\Modelo\Servicios\Reportes\ReporteAsistenciaService;
use App\Modelo\Servicios\Reportes\ReporteMovimientosService;
use App\Modelo\Servicios\Reportes\ReportePersonalService;
use App\Modelo\TipoMovimiento;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Capa CONTROLADOR - Reportes Administrativos (Sprint 5).
 *
 * No contiene reglas de negocio: la validacion vive en ReporteRequest,
 * la construccion de cada reporte en su generador, y la exportacion en
 * ExportacionService (patron Estrategia).
 *
 *   HU-13  RF-15  reporte de personal      -> personal()
 *   HU-14  RF-16  reporte de asistencia    -> asistencia()
 *   HU-15  RF-17  reporte de movimientos   -> movimientos()
 *   —      RF-18  exportar a PDF / Excel   -> responder()
 *
 * Los tres metodos siguen el mismo flujo, que es el que describe la
 * Figura 5.6: filtrar, generar, y bifurcar entre mostrar en pantalla o
 * exportar. Por eso comparten responder().
 */
class ReporteController extends Controller
{
    public function __construct(
        private readonly ReportePersonalService $personal,
        private readonly ReporteAsistenciaService $asistencia,
        private readonly ReporteMovimientosService $movimientos,
        private readonly ExportacionService $exportacion,
    ) {
    }

    /** Pantalla con las tres pestanas (Figuras 5.7 y 5.8). */
    public function index(): View
    {
        return view('reporte.index', $this->catalogos() + [
            'pestana' => 'personal',
            'reporte' => null,
            'filtros' => [],
        ]);
    }

    // -----------------------------------------------------------------
    //  HU-13 - Reporte de personal
    // -----------------------------------------------------------------

    public function personal(ReporteRequest $request): View|Response|RedirectResponse
    {
        $reporte = $this->personal->generar($request->filtros(), $this->areaRestringida());

        return $this->responder($request, $reporte, 'personal');
    }

    // -----------------------------------------------------------------
    //  HU-14 - Reporte de asistencia
    // -----------------------------------------------------------------

    public function asistencia(ReporteRequest $request): View|Response|RedirectResponse
    {
        $reporte = $this->asistencia->generar($request->filtros(), $this->areaRestringida());

        return $this->responder($request, $reporte, 'asistencia');
    }

    // -----------------------------------------------------------------
    //  HU-15 - Reporte de movimientos
    // -----------------------------------------------------------------

    public function movimientos(ReporteRequest $request): View|Response|RedirectResponse
    {
        $reporte = $this->movimientos->generar($request->filtros(), $this->areaRestringida());

        return $this->responder($request, $reporte, 'movimientos');
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    /**
     * Bifurcacion final de la Figura 5.6: mostrar o exportar.
     *
     * Es identica para los tres reportes; lo unico que cambia es la
     * fuente de datos, que ya viene resuelta en el ResultadoReporte.
     */
    private function responder(
        ReporteRequest $request,
        ResultadoReporte $reporte,
        string $pestana
    ): View|Response|RedirectResponse {
        $formato = $request->string('formato')->toString();

        if ($formato !== '') {
            try {
                return $this->exportacion->exportar($reporte, $formato);
            } catch (ReglaNegocioException $e) {
                return back()->withInput()->withErrors(['error' => $e->getMessage()]);
            }
        }

        // Consulta en pantalla: tambien queda registrada, porque acceder a
        // los datos del personal es un acceso auditable (RNF-10, RNF-12).
        $this->exportacion->registrarGeneracion($reporte, ExportacionService::PANTALLA);

        return view('reporte.index', $this->catalogos() + [
            'pestana' => $pestana,
            'reporte' => $reporte,
            'filtros' => $request->filtros(),
        ]);
    }

    /**
     * CA-HU03-03 heredado: el Jefe de Area solo reporta sobre su area.
     * Devuelve el id del area cuando corresponde restringir, o null.
     */
    private function areaRestringida(): ?int
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if ($usuario === null) {
            return null;
        }

        $esJefeRestringido = $usuario->tieneRol(Rol::JEFE_AREA)
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA, Rol::GERENTE_RED)
            && $usuario->areaId() !== null;

        return $esJefeRestringido ? $usuario->areaId() : null;
    }

    /**
     * Catalogos para dibujar los combos de los filtros.
     *
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        $areaRestringida = $this->areaRestringida();

        return [
            'establecimientos' => Establecimiento::orderBy('nombre')->get(),
            'areas' => Area::query()
                ->with('establecimiento')
                ->when($areaRestringida, fn (Builder $q) => $q->where('area_id', $areaRestringida))
                ->orderBy('nombre')
                ->get(),
            'personal' => Personal::query()
                ->with('area')
                ->when($areaRestringida, fn (Builder $q) => $q->where('area_id', $areaRestringida))
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get(),
            'tipos' => TipoMovimiento::orderBy('nombre')->get(),
            'condiciones' => Personal::CONDICIONES,
            'estadosAsistencia' => Asistencia::ESTADOS,
            'estadosMovimiento' => MovimientoInstitucional::ESTADOS,
            'exportadores' => $this->exportacion->disponibles(),
            'areaRestringida' => $areaRestringida,
        ];
    }
}
