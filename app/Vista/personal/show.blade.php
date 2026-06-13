{{--
    Capa VISTA - HU-18: Consulta del historial laboral del personal.
    Reune en una sola pantalla los datos del trabajador, sus asistencias y
    sus movimientos institucionales ordenados del mas reciente al mas
    antiguo (CA-HU18-01 y CA-HU18-02).
--}}
@extends('layouts.app')

@section('titulo', 'Historial laboral')
@section('subtitulo', 'HU-18 · '.$personal->nombre_completo)

@section('contenido')

    <nav aria-label="ruta" class="mb-3 no-imprimir">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('personal.index') }}">Padron</a></li>
            <li class="breadcrumb-item active">{{ $personal->nombre_completo }}</li>
        </ol>
    </nav>

    <div class="row g-3">

        {{-- ------------------------------------------------------- --}}
        {{--  Ficha del trabajador                                   --}}
        {{-- ------------------------------------------------------- --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-circle display-3 text-primary opacity-75"></i>
                    <h2 class="h5 mt-2 mb-1">{{ $personal->nombre_completo }}</h2>
                    <p class="text-muted mb-2">{{ $personal->cargo }}</p>
                    <span class="badge badge-estado text-bg-{{ $personal->es_activo ? 'success' : 'danger' }}">
                        {{ $personal->estado }}
                    </span>
                    <span class="badge text-bg-secondary">{{ $personal->condicion_laboral }}</span>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">DNI</span>
                        <span class="font-monospace">{{ $personal->dni }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Area</span>
                        <span class="text-end">{{ $personal->area?->nombre }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Establecimiento</span>
                        <span class="text-end">{{ $personal->area?->establecimiento?->nombre }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Microrred</span>
                        <span class="text-end">{{ $personal->area?->establecimiento?->microrred }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Horario</span>
                        <span class="text-end">
                            {{ $personal->horario?->etiqueta ?? 'Sin asignar' }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Fecha de ingreso</span>
                        <span>{{ $personal->fecha_ingreso?->format('d/m/Y') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Anios de servicio</span>
                        <span>{{ $personal->anios_servicio }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Telefono</span>
                        <span>{{ $personal->telefono ?: '—' }}</span>
                    </li>
                    <li class="list-group-item">
                        <span class="text-muted d-block">Correo</span>
                        <span class="text-break">{{ $personal->correo ?: '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Cuenta de acceso</span>
                        <span class="text-end">
                            @if ($personal->usuario)
                                {{ $personal->usuario->username }}
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $personal->usuario->roles->pluck('nombre')->implode(', ') ?: 'sin rol' }}
                                </div>
                            @else
                                <span class="text-muted">Sin cuenta</span>
                            @endif
                        </span>
                    </li>
                    @unless ($personal->es_activo)
                        <li class="list-group-item">
                            <span class="text-muted d-block">Motivo de la baja</span>
                            {{ $personal->motivo_baja ?: '—' }}
                        </li>
                    @endunless
                </ul>

                <div class="card-footer bg-white d-flex gap-2 no-imprimir">
                    @if (auth()->user()->tienePermiso('PADRON', 'EDITAR'))
                        <a href="{{ route('personal.edit', $personal) }}"
                           class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </a>
                    @endif
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- --}}
        {{--  Historial consolidado                                  --}}
        {{-- ------------------------------------------------------- --}}
        <div class="col-lg-8">

            {{-- Resumen --}}
            <div class="row g-3 mb-3">
                @foreach ([
                    ['Asistencias registradas', $asistencias->count(),                                'primary'],
                    ['Tardanzas',               $asistencias->where('estado','TARDANZA')->count(),    'warning'],
                    ['Faltas',                  $asistencias->where('estado','FALTA')->count(),       'danger'],
                    ['Movimientos',             $movimientos->count(),                                'info'],
                ] as [$titulo, $valor, $color])
                    <div class="col-6 col-md-3">
                        <div class="card kpi-card kpi-{{ $color }}">
                            <div class="card-body py-3">
                                <div class="kpi-titulo">{{ $titulo }}</div>
                                <div class="kpi-valor">{{ $valor }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header p-0 border-0">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-linea">
                                <i class="bi bi-list-ul me-1"></i> Linea de tiempo
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-asistencia">
                                Asistencias ({{ $asistencias->count() }})
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-movimientos">
                                Movimientos ({{ $movimientos->count() }})
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-auditoria">
                                Auditoria ({{ $auditoria->count() }})
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body tab-content">

                    {{-- Linea de tiempo consolidada --}}
                    <div class="tab-pane fade show active" id="tab-linea">
                        <div class="timeline">
                            @forelse ($lineaTiempo as $evento)
                                <div class="timeline-item">
                                    <span class="timeline-punto bg-{{ $evento['color'] }}"></span>
                                    <div class="d-flex justify-content-between">
                                        <strong class="small">
                                            {{ $evento['tipo'] }} · {{ $evento['titulo'] }}
                                        </strong>
                                        <span class="small text-muted">
                                            {{ $evento['fecha']?->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">{{ $evento['detalle'] }}</div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Sin eventos registrados.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Asistencias (datos del Sprint 2, aqui solo lectura) --}}
                    <div class="tab-pane fade" id="tab-asistencia">
                        <div class="table-responsive">
                            <table class="table table-sm tabla-padron mb-0">
                                <thead><tr>
                                    <th>Fecha</th><th>Entrada</th><th>Salida</th>
                                    <th>Estado</th><th>Tardanza</th><th>Origen</th>
                                </tr></thead>
                                <tbody>
                                @forelse ($asistencias as $a)
                                    <tr>
                                        <td>{{ $a->fecha?->format('d/m/Y') }}</td>
                                        <td>{{ $a->hora_entrada ?? '—' }}</td>
                                        <td>{{ $a->hora_salida ?? '—' }}</td>
                                        <td><span class="badge text-bg-{{ $a->color }}">{{ $a->estado }}</span></td>
                                        <td>{{ $a->minutos_tardanza > 0 ? $a->minutos_tardanza.' min' : '—' }}</td>
                                        <td class="small text-muted">{{ $a->origen }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">
                                        Sin asistencias registradas. El modulo de asistencia
                                        corresponde al Sprint 2.
                                    </td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Movimientos institucionales (datos del Sprint 3) --}}
                    <div class="tab-pane fade" id="tab-movimientos">
                        <div class="table-responsive">
                            <table class="table table-sm tabla-padron mb-0">
                                <thead><tr>
                                    <th>Tipo</th><th>Periodo</th><th>Dias</th>
                                    <th>Destino</th><th>Motivo</th><th>Estado</th>
                                </tr></thead>
                                <tbody>
                                @forelse ($movimientos as $m)
                                    <tr>
                                        <td>{{ $m->tipo?->nombre }}</td>
                                        <td class="small">
                                            {{ $m->fecha_inicio?->format('d/m/Y') }} –
                                            {{ $m->fecha_fin?->format('d/m/Y') }}
                                        </td>
                                        <td>{{ $m->dias }}</td>
                                        <td class="small">{{ $m->establecimientoDestino?->nombre ?? '—' }}</td>
                                        <td class="small">{{ $m->motivo }}</td>
                                        <td><span class="badge text-bg-{{ $m->color }}">{{ $m->estado }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">
                                        Sin movimientos registrados. El modulo de movimientos
                                        corresponde al Sprint 3.
                                    </td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Auditoria del registro (RNF-10) --}}
                    <div class="tab-pane fade" id="tab-auditoria">
                        <div class="table-responsive">
                            <table class="table table-sm tabla-padron mb-0">
                                <thead><tr>
                                    <th>Fecha</th><th>Accion</th><th>Usuario</th>
                                    <th>Detalle</th><th>IP</th>
                                </tr></thead>
                                <tbody>
                                @forelse ($auditoria as $log)
                                    <tr>
                                        <td class="small">{{ $log->fecha?->format('d/m/Y H:i') }}</td>
                                        <td><span class="badge text-bg-light">{{ $log->accion }}</span></td>
                                        <td class="small">{{ $log->usuario?->username ?? '—' }}</td>
                                        <td class="small">{{ $log->detalle }}</td>
                                        <td class="small font-monospace">{{ $log->ip_origen ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">
                                        Sin registros de auditoria para este trabajador.
                                    </td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
