{{-- Capa VISTA - Tablero del Sprint 3: Movimientos Institucionales. --}}
@extends('layouts.app')

@section('titulo', 'Tablero')
@section('subtitulo', 'Movimientos institucionales · Sprint 3')

@section('contenido')

    <div class="alert alert-primary d-flex align-items-center gap-3 border-0">
        <i class="bi bi-arrow-left-right fs-3"></i>
        <div>
            <strong>Bienvenido, {{ $usuario->nombre_mostrado }}.</strong><br>
            <span class="small">
                {{ $total }} movimiento(s) registrado(s) en su alcance
                @if ($usuario->personal?->area)
                    · {{ $usuario->personal->area->nombre }}
                @endif
            </span>
        </div>
    </div>

    {{-- Aviso de movimientos por cerrar --}}
    @if ($vencidos->isNotEmpty() && auth()->user()->tienePermiso('MOVIMIENTOS', 'EDITAR'))
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>{{ $vencidos->count() }} movimiento(s) aprobado(s) con periodo vencido.</strong>
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

    {{-- Indicadores por estado --}}
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

    <div class="row g-3">
        {{-- Distribución por tipo --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Movimientos por tipo</div>
                <div class="card-body">
                    @if ($porTipo->isEmpty())
                        <p class="text-muted small mb-0">Aun no hay movimientos registrados.</p>
                    @else
                        <div style="height:260px">
                            <canvas id="grafico-condicion"
                                    data-etiquetas='@json($porTipo->keys())'
                                    data-valores='@json($porTipo->values())'></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Personal fuera de su puesto hoy --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Personal con movimiento en curso hoy</span>
                    <a href="{{ route('movimiento.index') }}" class="small text-decoration-none">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 tabla-padron">
                        <thead class="table-light">
                            <tr><th>Trabajador</th><th>Tipo</th><th>Destino</th><th>Hasta</th></tr>
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
                                <td class="small">{{ $m->establecimientoDestino?->nombre ?? '—' }}</td>
                                <td class="small">{{ $m->fecha_fin?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted small py-4">
                                Nadie tiene un movimiento aprobado en curso hoy.
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Pendientes de decisión --}}
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-hourglass-split text-warning me-1"></i>
                Pendientes de decision
            </span>
            <span class="badge text-bg-warning">{{ $porEstado['PENDIENTE'] ?? 0 }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 tabla-padron">
                <thead class="table-light">
                    <tr><th>Trabajador</th><th>Area</th><th>Tipo</th><th>Periodo</th><th>Dias</th><th>Motivo</th></tr>
                </thead>
                <tbody>
                @forelse ($pendientes as $m)
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
                        <td class="small text-truncate" style="max-width:22rem">{{ $m->motivo }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        No hay movimientos esperando decision.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
