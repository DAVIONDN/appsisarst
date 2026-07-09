<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Modelo\MovimientoInstitucional;
use App\Modelo\Rol;
use App\Modelo\TipoMovimiento;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Tablero del Sprint 3.
 *
 * Consolida los indicadores del ciclo de vida de los movimientos
 * institucionales. Los del padron y la asistencia son de los sprints 1 y 2.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $usuario->load('roles', 'personal.area.establecimiento');

        $hoy = now()->toDateString();

        $porEstado = collect(MovimientoInstitucional::ESTADOS)
            ->mapWithKeys(fn (string $e) => [
                $e => (int) $this->alcance()->where('estado', $e)->count(),
            ]);

        return view('dashboard', [
            'usuario' => $usuario,
            'porEstado' => $porEstado,
            'total' => $porEstado->sum(),

            // Movimientos que hoy tienen al trabajador fuera de su puesto
            'enCurso' => $this->alcance()
                ->where('estado', MovimientoInstitucional::APROBADO)
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->with(['personal.area', 'tipo', 'establecimientoDestino'])
                ->orderBy('fecha_fin')
                ->get(),

            // Aprobados cuyo periodo ya termino: esperan ser finalizados
            'vencidos' => $this->alcance()
                ->where('estado', MovimientoInstitucional::APROBADO)
                ->whereDate('fecha_fin', '<', $hoy)
                ->with(['personal', 'tipo'])
                ->orderBy('fecha_fin')
                ->get(),

            // Pendientes de decision
            'pendientes' => $this->alcance()
                ->where('estado', MovimientoInstitucional::PENDIENTE)
                ->with(['personal.area', 'tipo'])
                ->orderBy('fecha_inicio')
                ->limit(8)
                ->get(),

            'porTipo' => $this->totalesPorTipo(),
        ]);
    }

    /**
     * Totales por tipo de movimiento, contando solo los que siguen
     * comprometiendo al trabajador o ya se cumplieron.
     *
     * @return \Illuminate\Support\Collection<string, int>
     */
    private function totalesPorTipo()
    {
        return TipoMovimiento::query()
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (TipoMovimiento $t) => [
                str_replace('_', ' ', $t->nombre) => (int) $this->alcance()
                    ->where('tipo_movimiento_id', $t->tipo_movimiento_id)
                    ->count(),
            ])
            ->filter(fn (int $total) => $total > 0);
    }

    /** El Jefe de Area solo ve los movimientos del personal de su area. */
    private function alcance(): Builder
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $esJefeRestringido = $usuario->tieneRol(Rol::JEFE_AREA)
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA, Rol::GERENTE_RED)
            && $usuario->areaId() !== null;

        return MovimientoInstitucional::query()->when(
            $esJefeRestringido,
            fn (Builder $q) => $q->whereHas('personal', fn (Builder $p) => $p->where('area_id', $usuario->areaId()))
        );
    }
}
