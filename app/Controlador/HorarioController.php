<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Controlador\Validaciones\AsignarHorarioRequest;
use App\Controlador\Validaciones\HorarioRequest;
use App\Modelo\Excepciones\ReglaNegocioException;
use App\Modelo\Horario;
use App\Modelo\Personal;
use App\Modelo\Servicios\HorarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Horarios de trabajo (HU-16 / RF-19).
 *
 * HU-16 es requisito previo de HU-07: sin horario asignado el sistema no
 * puede evaluar tardanzas ni faltas (CA-HU16-03).
 *
 *   CA-HU16-01  registrar horario con sus datos  -> create() / store()
 *   CA-HU16-02  asignar a uno o varios           -> asignarForm() / asignar()
 *   CA-HU16-03  advertir al personal sin horario -> index()
 */
class HorarioController extends Controller
{
    public function __construct(private readonly HorarioService $horarios)
    {
    }

    public function index(Request $request): View
    {
        $horarios = Horario::query()
            ->buscar($request->query('buscar'))
            ->withCount(['personal as asignados_count' => fn ($q) => $q->where('estado', Personal::ESTADO_ACTIVO)])
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('horario.index', [
            'horarios' => $horarios,
            'buscar' => $request->query('buscar'),
            // CA-HU16-03: el sistema advierte de quien no puede ser evaluado.
            'sinHorario' => Personal::query()
                ->activo()
                ->whereNull('horario_id')
                ->with('area')
                ->orderBy('apellidos')
                ->get(),
        ]);
    }

    // -----------------------------------------------------------------
    //  CA-HU16-01 - Alta y edicion del catalogo
    // -----------------------------------------------------------------

    public function create(): View
    {
        return view('horario.create', ['dias' => Horario::DIAS_LABORALES]);
    }

    public function store(HorarioRequest $request): RedirectResponse
    {
        try {
            $horario = $this->horarios->registrar($request->datosNormalizados());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('horario.index')
            ->with('exito', "Horario {$horario->nombre} registrado correctamente.");
    }

    public function edit(Horario $horario): View
    {
        return view('horario.edit', [
            'horario' => $horario,
            'dias' => Horario::DIAS_LABORALES,
        ]);
    }

    public function update(HorarioRequest $request, Horario $horario): RedirectResponse
    {
        try {
            $this->horarios->actualizar($horario, $request->datosNormalizados());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('horario.index')
            ->with('exito', "Horario {$horario->nombre} actualizado correctamente.");
    }

    public function desactivar(Horario $horario): RedirectResponse
    {
        try {
            $this->horarios->desactivar($horario);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('horario.index')
            ->with('exito', "Horario {$horario->nombre} desactivado.");
    }

    public function reactivar(Horario $horario): RedirectResponse
    {
        try {
            $this->horarios->reactivar($horario);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('horario.index')
            ->with('exito', "Horario {$horario->nombre} reactivado.");
    }

    // -----------------------------------------------------------------
    //  CA-HU16-02 - Asignacion a uno o varios trabajadores
    // -----------------------------------------------------------------

    public function asignarForm(Horario $horario): View
    {
        return view('horario.asignar', [
            'horario' => $horario,
            'personal' => Personal::query()
                ->activo()
                ->with(['area.establecimiento', 'horario'])
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get(),
        ]);
    }

    public function asignar(AsignarHorarioRequest $request, Horario $horario): RedirectResponse
    {
        try {
            $total = $this->horarios->asignar($horario, $request->input('personal_ids', []));
        } catch (ReglaNegocioException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('horario.index')
            ->with('exito', "Horario {$horario->nombre} asignado a {$total} trabajador(es).");
    }

    /** Retira el horario a un trabajador; deja de ser evaluado (CA-HU16-03). */
    public function quitar(Personal $personal): RedirectResponse
    {
        $this->horarios->quitarA($personal);

        return back()->with('exito', "Se retiro el horario a {$personal->nombre_completo}.");
    }
}
