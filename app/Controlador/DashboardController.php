<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Modelo\Area;
use App\Modelo\Establecimiento;
use App\Modelo\LogAuditoria;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Tablero del sistema.
 *
 * Corresponde a la Figura 1.16 del documento de diseno (Mockup: Dashboard
 * del Sistema). En el Sprint 1 solo consolida indicadores del padron;
 * los de asistencia, movimientos y reportes se agregan en sus sprints.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $usuario->load('roles', 'personal.area.establecimiento');

        return view('dashboard', [
            'usuario' => $usuario,
            'totalActivos' => (int) $this->alcance()->activo()->count(),
            'totalInactivos' => (int) $this->alcance()->where('personal.estado', Personal::ESTADO_INACTIVO)->count(),
            'totalSinHorario' => (int) $this->alcance()->activo()->whereNull('horario_id')->count(),
            'totalEstablecimientos' => Establecimiento::where('activo', true)->count(),
            'totalAreas' => Area::activas()->count(),
            'porCondicion' => $this->alcance()
                ->activo()
                ->selectRaw('condicion_laboral, COUNT(*) AS total')
                ->groupBy('condicion_laboral')
                ->orderByDesc('total')
                ->pluck('total', 'condicion_laboral'),
            'porArea' => $this->alcance()
                ->activo()
                ->join('area', 'area.area_id', '=', 'personal.area_id')
                ->selectRaw('area.nombre AS area, COUNT(*) AS total')
                ->groupBy('area.nombre')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'area'),
            'ultimosMovimientos' => LogAuditoria::query()
                ->where('entidad', 'personal')
                ->with('usuario.personal')
                ->orderByDesc('fecha')
                ->limit(8)
                ->get(),
            'recientes' => $this->alcance()
                ->with('area')
                ->activo()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    /** Limita los indicadores al area del Jefe de Area (CA-HU03-03). */
    private function alcance(): Builder
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $query = Personal::query();

        $esJefeRestringido = $usuario->tieneRol(Rol::JEFE_AREA)
            && ! $usuario->tieneRol(Rol::ADMIN_RRHH, Rol::ADMIN_SISTEMA, Rol::GERENTE_RED)
            && $usuario->areaId() !== null;

        return $esJefeRestringido
            ? $query->where('personal.area_id', $usuario->areaId())
            : $query;
    }
}
