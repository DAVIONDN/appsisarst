{{-- Capa VISTA - Tablero del sistema (Figura 1.16 del documento de diseno). --}}
@extends('layouts.app')

@section('titulo', 'Tablero')
@section('subtitulo', 'Resumen del padron de personal · Sprint 1')

@section('contenido')

    <div class="alert alert-primary d-flex align-items-center gap-3 border-0">
        <i class="bi bi-person-badge fs-3"></i>
        <div>
            <strong>Bienvenido, {{ $usuario->nombre_mostrado }}.</strong><br>
            <span class="small">
                @if ($usuario->personal?->area)
                    {{ $usuario->personal->area->nombre }} ·
                    {{ $usuario->personal->area->establecimiento->nombre }}
                @else
                    Cuenta sin ficha de personal asociada.
                @endif
                @if ($usuario->ultimo_acceso)
                    · Ultimo acceso: {{ $usuario->ultimo_acceso->format('d/m/Y H:i') }}
                @endif
            </span>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Personal activo',    $totalActivos,         'success', 'bi-people-fill'],
            ['Personal inactivo',  $totalInactivos,       'danger',  'bi-person-dash'],
            ['Sin horario',        $totalSinHorario,      'warning', 'bi-clock-history'],
            ['Establecimientos',   $totalEstablecimientos,'info',    'bi-hospital'],
            ['Areas de trabajo',   $totalAreas,           '',        'bi-diagram-3'],
        ] as [$titulo, $valor, $color, $icono])
            <div class="col-6 col-lg">
                <div class="card kpi-card {{ $color ? 'kpi-'.$color : '' }} h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-titulo">{{ $titulo }}</div>
                            <div class="kpi-valor">{{ $valor }}</div>
                        </div>
                        <i class="bi {{ $icono }} fs-2 text-{{ $color ?: 'primary' }} opacity-50"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- Grafico por condicion laboral --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Personal activo por condicion laboral</div>
                <div class="card-body">
                    @if ($porCondicion->isEmpty())
                        <p class="text-muted small mb-0">Aun no hay personal registrado.</p>
                    @else
                        <div style="height:260px">
                            <canvas id="grafico-condicion"
                                    data-etiquetas='@json($porCondicion->keys())'
                                    data-valores='@json($porCondicion->values())'></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Grafico por area --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Personal activo por area</div>
                <div class="card-body">
                    @if ($porArea->isEmpty())
                        <p class="text-muted small mb-0">Aun no hay personal registrado.</p>
                    @else
                        <div style="height:260px">
                            <canvas id="grafico-area"
                                    data-etiquetas='@json($porArea->keys())'
                                    data-valores='@json($porArea->values())'></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-0">
        {{-- Ultimas altas --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Ultimas incorporaciones</span>
                    <a href="{{ route('personal.index') }}" class="small text-decoration-none">Ver padron</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 tabla-padron">
                        <thead>
                            <tr><th>DNI</th><th>Trabajador</th><th>Area</th><th>Ingreso</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($recientes as $p)
                            <tr>
                                <td class="font-monospace">{{ $p->dni }}</td>
                                <td>
                                    <a href="{{ route('personal.show', $p) }}"
                                       class="text-decoration-none">{{ $p->nombre_completo }}</a>
                                </td>
                                <td class="small">{{ $p->area?->nombre }}</td>
                                <td class="small">{{ $p->fecha_ingreso?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted small py-3">Sin registros.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Auditoria (RNF-10) --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Actividad reciente sobre el padron</div>
                <div class="card-body py-2">
                    <div class="timeline mt-2">
                        @forelse ($ultimosMovimientos as $log)
                            <div class="timeline-item">
                                <span class="timeline-punto bg-primary"></span>
                                <div class="small fw-semibold">{{ $log->accion }}</div>
                                <div class="small text-muted">{{ $log->detalle }}</div>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $log->fecha?->format('d/m/Y H:i') }} ·
                                    {{ $log->usuario?->username ?? 'sistema' }}
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Sin actividad registrada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
