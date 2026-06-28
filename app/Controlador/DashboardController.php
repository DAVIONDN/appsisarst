<?php

declare(strict_types=1);

namespace App\Controlador;

use App\Modelo\Asistencia;
use App\Modelo\Personal;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Capa CONTROLADOR - Tablero del sistema (Sprint 1 + Sprint 2).
 *
 * Sprint 1: indicadores del padron de personal.
 * Sprint 2: KPIs de asistencia del dia y del mes en curso.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $usuario->load('roles', 'personal.area.establecimiento');

        $hoy       = Carbon::now('America/Lima')->toDateString();
        $inicioMes = Carbon::now('America/Lima')->startOfMonth()->toDateString();

        // -- Alcance de personal segun rol --------------------------------
        $activos = (int) $this->alcance()->activo()->count();

        // -- KPIs del dia -------------------------------------------------
        $asistenciasHoy = Asistencia::whereDate('fecha', $hoy)->get();

        $puntualesHoy   = $asistenciasHoy->where('estado', Asistencia::PUNTUAL)->count();
        $tardanzasHoy   = $asistenciasHoy->where('estado', Asistencia::TARDANZA)->count();
        $faltasHoy      = $asistenciasHoy->where('estado', Asistencia::FALTA)->count();
        $justificadosHoy = $asistenciasHoy->where('estado', Asistencia::JUSTIFICADO)->count();
        $marcadosHoy    = $asistenciasHoy->count();
        $pendientesHoy  = max(0, $activos - $marcadosHoy);

        // -- KPIs del mes -------------------------------------------------
        $porEstadoMes = Asistencia::whereBetween('fecha', [$inicioMes, $hoy])
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $totalMes       = (int) $porEstadoMes->sum();
        $puntualesMes   = (int) ($porEstadoMes[Asistencia::PUNTUAL]   ?? 0);
        $tardanzasMes   = (int) ($porEstadoMes[Asistencia::TARDANZA]  ?? 0);
        $cumplimientoMes = $totalMes > 0
            ? (int) round(($puntualesMes + $tardanzasMes) / $totalMes * 100)
            : 0;

        $minutosMes = (int) Asistencia::whereBetween('fecha', [$inicioMes, $hoy])
            ->where('estado', Asistencia::TARDANZA)
            ->sum('minutos_tardanza');

        // -- Jornadas abiertas hoy ----------------------------------------
        $jornadasAbiertas = Asistencia::abiertas()
            ->whereDate('fecha', $hoy)
            ->with(['personal.area'])
            ->orderBy('hora_entrada')
            ->get();

        // -- Ultimos registros de asistencia ------------------------------
        $ultimas = Asistencia::with(['personal.area'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_entrada')
            ->limit(10)
            ->get();

        // -- Personal sin horario asignado (alerta HU-16) -----------------
        $sinHorario = (int) $this->alcance()->activo()->whereNull('horario_id')->count();

        return view('dashboard', [
            'usuario'          => $usuario,
            'hoy'              => $hoy,
            // KPIs del dia
            'activos'          => $activos,
            'sinHorario'       => $sinHorario,
            'puntualesHoy'     => $puntualesHoy,
            'tardanzasHoy'     => $tardanzasHoy,
            'faltasHoy'        => $faltasHoy,
            'justificadosHoy'  => $justificadosHoy,
            'pendientesHoy'    => $pendientesHoy,
            // KPIs del mes
            'porEstadoMes'     => $porEstadoMes,
            'cumplimientoMes'  => $cumplimientoMes,
            'minutosMes'       => $minutosMes,
            // Tablas
            'jornadasAbiertas' => $jornadasAbiertas,
            'ultimas'          => $ultimas,
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
