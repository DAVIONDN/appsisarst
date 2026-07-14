<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Modelo\Asistencia;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\TipoMovimiento;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Tablero unificado (Sprints 1, 2 y 3).
 *
 * Consolida los indicadores de los tres modulos operativos:
 *   Sprint 1 – Padron de Personal
 *   Sprint 2 – Control de Asistencia
 *   Sprint 3 – Movimientos Institucionales
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $usuario->load('roles', 'personal.area.establecimiento');

        $hoy = Carbon::now('America/Lima')->toDateString();

        // ─────────────────────────────────────────────────────────────
        //  Sprint 1 – Padrón de Personal
        // ─────────────────────────────────────────────────────────────
        $totalPersonal = Personal::count();
        $activos       = Personal::where('estado', Personal::ESTADO_ACTIVO)->count();
        $inactivos     = Personal::where('estado', Personal::ESTADO_INACTIVO)->count();

        // Últimas incorporaciones (HU-01)
        $ultimas = Personal::with('area')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ─────────────────────────────────────────────────────────────
        //  Sprint 2 – Asistencia de hoy
        // ─────────────────────────────────────────────────────────────
        $asistenciaHoy = Asistencia::whereDate('fecha', $hoy);

        $puntualesHoy  = (clone $asistenciaHoy)->where('estado', Asistencia::PUNTUAL)->count();
        $tardanzasHoy  = (clone $asistenciaHoy)->where('estado', Asistencia::TARDANZA)->count();
        $faltasHoy     = (clone $asistenciaHoy)->where('estado', Asistencia::FALTA)->count();
        $justificados  = (clone $asistenciaHoy)->where('estado', Asistencia::JUSTIFICADO)->count();

        // Personal sin horario asignado
        $sinHorario = Personal::where('estado', Personal::ESTADO_ACTIVO)
            ->whereNull('horario_id')
            ->count();

        // ─────────────────────────────────────────────────────────────
        //  Sprint 3 – Movimientos Institucionales
        // ─────────────────────────────────────────────────────────────
        $porEstado = collect(MovimientoInstitucional::ESTADOS)
            ->mapWithKeys(fn (string $e) => [
                $e => (int) $this->alcanceMovimientos()->where('estado', $e)->count(),
            ]);

        // Movimientos aprobados en curso hoy
        $enCurso = $this->alcanceMovimientos()
            ->where('estado', MovimientoInstitucional::APROBADO)
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->with(['personal.area', 'tipo', 'establecimientoDestino'])
            ->orderBy('fecha_fin')
            ->get();

        // Aprobados con periodo vencido (candidatos a FINALIZADO)
        $movVencidos = $this->alcanceMovimientos()
            ->where('estado', MovimientoInstitucional::APROBADO)
            ->whereDate('fecha_fin', '<', $hoy)
            ->with(['personal', 'tipo'])
            ->orderBy('fecha_fin')
            ->get();

        // Pendientes de decision (primeros 8)
        $movPendientes = $this->alcanceMovimientos()
            ->where('estado', MovimientoInstitucional::PENDIENTE)
            ->with(['personal.area', 'tipo'])
            ->orderBy('fecha_inicio')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'usuario'       => $usuario,

            // Sprint 1
            'totalPersonal' => $totalPersonal,
            'activos'       => $activos,
            'inactivos'     => $inactivos,
            'ultimas'       => $ultimas,

            // Sprint 2
            'hoy'           => $hoy,
            'puntualesHoy'  => $puntualesHoy,
            'tardanzasHoy'  => $tardanzasHoy,
            'faltasHoy'     => $faltasHoy,
            'justificados'  => $justificados,
            'sinHorario'    => $sinHorario,

            // Sprint 3
            'porEstado'     => $porEstado,
            'totalMov'      => $porEstado->sum(),
            'enCurso'       => $enCurso,
            'movVencidos'   => $movVencidos,
            'movPendientes' => $movPendientes,
            'porTipo'       => $this->totalesPorTipo(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    //  Apoyo interno
    // ─────────────────────────────────────────────────────────────────

    /** El Jefe de Area solo ve los movimientos del personal de su area. */
    private function alcanceMovimientos(): Builder
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $esJefeRestringido = $usuario->tieneRol(Rol::JEFE_AREA)
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA, Rol::GERENTE_RED)
            && $usuario->areaId() !== null;

        return MovimientoInstitucional::query()->when(
            $esJefeRestringido,
            fn (Builder $q) => $q->whereHas(
                'personal',
                fn (Builder $p) => $p->where('area_id', $usuario->areaId())
            )
        );
    }

    /**
     * Totales por tipo de movimiento (solo tipos con al menos 1 registro).
     *
     * @return \Illuminate\Support\Collection<string, int>
     */
    private function totalesPorTipo()
    {
        return TipoMovimiento::query()
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (TipoMovimiento $t) => [
                str_replace('_', ' ', $t->nombre) => (int) $this->alcanceMovimientos()
                    ->where('tipo_movimiento_id', $t->tipo_movimiento_id)
                    ->count(),
            ])
            ->filter(fn (int $total) => $total > 0);
    }
}
