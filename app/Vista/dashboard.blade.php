{{-- Capa VISTA - Tablero del Sprint 2: Control de Asistencia. --}}
@extends('layouts.app')

@section('titulo', 'Tablero')
@section('subtitulo', 'Control de asistencia · Sprint 2')

@section('contenido')

    <div class="alert alert-primary d-flex align-items-center gap-3 border-0">
        <i class="bi bi-calendar-check fs-3"></i>
        <div>
            <strong>Bienvenido, {{ $usuario->nombre_mostrado }}.</strong><br>
            <span class="small">
                Jornada del {{ \Illuminate\Support\Carbon::parse($hoy)->format('d/m/Y') }}
                @if ($usuario->personal?->area)
                    · {{ $usuario->personal->area->nombre }}
                @endif
                @if ($usuario->ultimo_acceso)
                    · Ultimo acceso: {{ $usuario->ultimo_acceso->format('d/m/Y H:i') }}
                @endif
            </span>
        </div>
    </div>

    {{-- CA-HU16-03: advertencia de personal no evaluable --}}
    @if ($sinHorario > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>{{ $sinHorario }} trabajador(es) activo(s) sin horario asignado.</strong>
                El sistema no puede evaluar sus tardanzas ni generar sus faltas automaticas.
            </div>
            <a href="{{ route('horario.index') }}" class="btn btn-sm btn-warning text-dark">
                Asignar horarios
            </a>
        </div>
    @endif

    {{-- Indicadores del dia --}}
    <h2 class="h6 text-muted text-uppercase mb-2" style="letter-spacing:.08em">Jornada de hoy</h2>
    <div class="row g-3 mb-4">
        @foreach ([
            ['Personal activo',  $activos,          '',        'bi-people-fill'],
            ['Puntuales',        $puntualesHoy,     'success', 'bi-check-circle-fill'],
            ['Tardanzas',        $tardanzasHoy,     'warning', 'bi-clock-fill'],
            ['Faltas',           $faltasHoy,        'danger',  'bi-x-circle-fill'],
            ['Justificados',     $justificadosHoy,  'info',    'bi-file-earmark-text-fill'],
            ['Sin marcar',       $pendientesHoy,    '',        'bi-hourglass-split'],
        ] as [$titulo, $valor, $color, $icono])
            <div class="col-6 col-lg-2">
                <div class="card kpi-card {{ $color ? 'kpi-'.$color : '' }} h-100">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="kpi-titulo">{{ $titulo }}</div>
                            <div class="kpi-valor">{{ $valor }}</div>
                        </div>
                        <i class="bi {{ $icono }} fs-3 text-{{ $color ?: 'primary' }} opacity-50"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- Distribucion del mes --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Asistencia del mes</span>
                    <span class="badge text-bg-{{ $cumplimientoMes >= 90 ? 'success' : ($cumplimientoMes >= 70 ? 'warning' : 'danger') }}">
                        {{ $cumplimientoMes }}% cumplimiento
                    </span>
                </div>
                <div class="card-body">
                    @if ($porEstadoMes->isEmpty())
                        <p class="text-muted small mb-0">Aun no hay registros de asistencia este mes.</p>
                    @else
                        <div style="height:240px">
                            <canvas id="grafico-condicion"
                                    data-etiquetas='@json($porEstadoMes->keys())'
                                    data-valores='@json($porEstadoMes->values())'></canvas>
                        </div>
                        <p class="small text-muted mt-3 mb-0">
                            Total acumulado de tardanza en el mes:
                            <strong>{{ $minutosMes }} minutos</strong>.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Jornadas abiertas --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Jornadas abiertas hoy <small class="text-muted fw-normal">(entrada sin salida)</small></span>
                    <a href="{{ route('asistencia.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-clock me-1"></i> Marcar asistencia
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 tabla-padron">
                        <thead class="table-light">
                            <tr><th>Trabajador</th><th>Entrada</th><th>Estado</th><th>Tardanza</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($jornadasAbiertas as $a)
                            <tr>
                                <td>{{ $a->personal?->nombre_completo }}</td>
                                <td class="font-monospace">{{ $a->entrada_corta }}</td>
                                <td><span class="badge text-bg-{{ $a->color }}">{{ $a->estado }}</span></td>
                                <td>{{ $a->minutos_tardanza > 0 ? $a->minutos_tardanza.' min' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted small py-4">
                                No hay jornadas abiertas.
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Ultimos registros --}}
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Ultimos registros de asistencia</span>
            <a href="{{ route('asistencia.index') }}" class="small text-decoration-none">Ver todos</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 tabla-padron">
                <thead class="table-light">
                    <tr><th>Fecha</th><th>Trabajador</th><th>Area</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Origen</th></tr>
                </thead>
                <tbody>
                @forelse ($ultimas as $a)
                    <tr>
                        <td class="small">{{ $a->fecha?->format('d/m/Y') }}</td>
                        <td>{{ $a->personal?->nombre_completo }}</td>
                        <td class="small">{{ $a->personal?->area?->nombre }}</td>
                        <td class="font-monospace small">{{ $a->entrada_corta }}</td>
                        <td class="font-monospace small">{{ $a->salida_corta }}</td>
                        <td>
                            <span class="badge text-bg-{{ $a->color }}">
                                <i class="bi {{ $a->icono }}"></i> {{ $a->estado }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $a->origen }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        Sin registros de asistencia todavia.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
