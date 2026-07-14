{{-- Capa VISTA - Tablero unificado: Sprints 1, 2 y 3. --}}
@extends('layouts.app')

@section('titulo', 'Tablero')
@section('subtitulo', 'Padrón · Asistencia · Movimientos')

@section('contenido')

    {{-- Bienvenida --}}
    <div class="alert alert-primary d-flex align-items-center gap-3 border-0 mb-4">
        <i class="bi bi-speedometer2 fs-3"></i>
        <div>
            <strong>Bienvenido, {{ $usuario->nombre_mostrado }}.</strong><br>
            <span class="small">
                Sistema de Gestión del Personal · Red de Salud Tayacaja ·
                {{ \Illuminate\Support\Carbon::parse($hoy)->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            </span>
        </div>
    </div>

    {{-- Aviso movimientos vencidos --}}
    @if ($movVencidos->isNotEmpty() && auth()->user()->tienePermiso('MOVIMIENTOS', 'EDITAR'))
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
            <div>
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>{{ $movVencidos->count() }} movimiento(s) aprobado(s) con periodo vencido.</strong>
                Corresponde cerrarlos como FINALIZADO.
            </div>
            <form method="POST" action="{{ route('movimiento.finalizar-vencidos') }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-warning text-dark" type="submit">
                    <i class="bi bi-flag me-1"></i> Finalizar vencidos
                </button>
            </form>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{--  SPRINT 1 — Padrón de Personal                        --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge text-bg-secondary">Sprint 1</span>
        <h6 class="mb-0 text-muted">Padrón de Personal</h6>
        <hr class="flex-grow-1 my-0">
        @if (auth()->user()->tienePermiso('PADRON', 'LEER'))
            <a href="{{ route('personal.index') }}" class="small text-decoration-none">Ver padrón</a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card kpi-card kpi-info h-100">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Total personal</div>
                        <div class="kpi-valor">{{ $totalPersonal }}</div>
                    </div>
                    <i class="bi bi-people fs-3 text-info opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card kpi-card kpi-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Activos</div>
                        <div class="kpi-valor">{{ $activos }}</div>
                    </div>
                    <i class="bi bi-person-check-fill fs-3 text-success opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card kpi-card kpi-danger h-100">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Inactivos</div>
                        <div class="kpi-valor">{{ $inactivos }}</div>
                    </div>
                    <i class="bi bi-person-x-fill fs-3 text-danger opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{--  SPRINT 2 — Asistencia de hoy                         --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge text-bg-secondary">Sprint 2</span>
        <h6 class="mb-0 text-muted">Asistencia de hoy</h6>
        <hr class="flex-grow-1 my-0">
        @if (auth()->user()->tienePermiso('ASISTENCIA', 'LEER'))
            <a href="{{ route('asistencia.index') }}" class="small text-decoration-none">Ver asistencia</a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card kpi-card kpi-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Puntuales</div>
                        <div class="kpi-valor">{{ $puntualesHoy }}</div>
                    </div>
                    <i class="bi bi-check-circle-fill fs-3 text-success opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card kpi-warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Tardanzas</div>
                        <div class="kpi-valor">{{ $tardanzasHoy }}</div>
                    </div>
                    <i class="bi bi-clock-fill fs-3 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card kpi-danger h-100">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Faltas</div>
                        <div class="kpi-valor">{{ $faltasHoy }}</div>
                    </div>
                    <i class="bi bi-x-circle-fill fs-3 text-danger opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100" style="border-left: 4px solid var(--bs-info)">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="kpi-titulo">Sin horario</div>
                        <div class="kpi-valor">{{ $sinHorario }}</div>
                    </div>
                    <i class="bi bi-calendar-x fs-3 text-secondary opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{--  SPRINT 3 — Movimientos Institucionales               --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge text-bg-secondary">Sprint 3</span>
        <h6 class="mb-0 text-muted">Movimientos Institucionales</h6>
        <hr class="flex-grow-1 my-0">
        @if (auth()->user()->tienePermiso('MOVIMIENTOS', 'LEER'))
            <a href="{{ route('movimiento.index') }}" class="small text-decoration-none">Ver movimientos</a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Pendientes',  'PENDIENTE',  'warning',   'bi-hourglass-split'],
            ['Aprobados',   'APROBADO',   'success',   'bi-check-circle-fill'],
            ['Rechazados',  'RECHAZADO',  'danger',    'bi-x-circle-fill'],
            ['Finalizados', 'FINALIZADO', 'secondary', 'bi-flag-fill'],
        ] as [$titulo, $clave, $color, $icono])
            <div class="col-6 col-lg-3">
                <div class="card kpi-card kpi-{{ $color === 'secondary' ? 'info' : $color }} h-100">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="kpi-titulo">{{ $titulo }}</div>
                            <div class="kpi-valor">{{ $porEstado[$clave] ?? 0 }}</div>
                        </div>
                        <i class="bi {{ $icono }} fs-3 text-{{ $color }} opacity-50"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tablas de detalle: últimas incorporaciones y movimientos en curso --}}
    <div class="row g-3">

        {{-- Últimas incorporaciones (Sprint 1) --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-plus me-1 text-primary"></i> Últimas incorporaciones</span>
                    @if (auth()->user()->tienePermiso('PADRON', 'LEER'))
                        <a href="{{ route('personal.index') }}" class="small text-decoration-none">Ver todos</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 tabla-padron">
                        <thead class="table-light">
                            <tr><th>Personal</th><th>Área</th><th>Ingreso</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($ultimas as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('personal.show', $p) }}" class="text-decoration-none fw-semibold">
                                        {{ $p->nombre_completo }}
                                    </a>
                                    <div class="text-muted font-monospace" style="font-size:.72rem">{{ $p->dni }}</div>
                                </td>
                                <td class="small">{{ $p->area?->nombre ?? '—' }}</td>
                                <td class="small">{{ $p->fecha_ingreso?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted small py-3">Sin registros.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Personal con movimiento en curso hoy (Sprint 3) --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-arrow-left-right me-1 text-success"></i> Movimientos en curso hoy</span>
                    @if (auth()->user()->tienePermiso('MOVIMIENTOS', 'LEER'))
                        <a href="{{ route('movimiento.index') }}" class="small text-decoration-none">Ver todos</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 tabla-padron">
                        <thead class="table-light">
                            <tr><th>Trabajador</th><th>Tipo</th><th>Hasta</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($enCurso as $m)
                            <tr>
                                <td>
                                    <a href="{{ route('movimiento.show', $m) }}" class="text-decoration-none">
                                        {{ $m->personal?->nombre_completo }}
                                    </a>
                                    <div class="text-muted small">{{ $m->personal?->area?->nombre }}</div>
                                </td>
                                <td class="small">{{ str_replace('_', ' ', $m->tipo?->nombre ?? '') }}</td>
                                <td class="small">{{ $m->fecha_fin?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted small py-3">
                                Nadie tiene un movimiento aprobado en curso hoy.
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Pendientes de decisión (Sprint 3) --}}
    @if ($movPendientes->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-hourglass-split text-warning me-1"></i>
                    Movimientos pendientes de decisión
                </span>
                <span class="badge text-bg-warning">{{ $porEstado['PENDIENTE'] ?? 0 }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 tabla-padron">
                    <thead class="table-light">
                        <tr><th>Trabajador</th><th>Área</th><th>Tipo</th><th>Periodo</th><th>Días</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($movPendientes as $m)
                        <tr>
                            <td>
                                <a href="{{ route('movimiento.show', $m) }}" class="text-decoration-none fw-semibold">
                                    {{ $m->personal?->nombre_completo }}
                                </a>
                            </td>
                            <td class="small">{{ $m->personal?->area?->nombre }}</td>
                            <td class="small">{{ str_replace('_', ' ', $m->tipo?->nombre ?? '') }}</td>
                            <td class="small">{{ $m->periodo }}</td>
                            <td class="small">{{ $m->dias }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
