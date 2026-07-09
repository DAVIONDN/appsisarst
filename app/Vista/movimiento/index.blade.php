{{--
    Capa VISTA - HU-09: Consulta de movimientos institucionales.
    Figuras 3.7 y 3.8 del documento de diseno.
    Filtros por personal, tipo, estado, area y periodo (CA-HU09-01), con
    los estados diferenciados por color (CA-HU09-03) y ordenados del mas
    reciente al mas antiguo (CA-HU09-02).
--}}
@extends('layouts.app')

@section('titulo', 'Movimientos Institucionales')
@section('subtitulo', 'HU-09 · Consulta e historial')

@section('contenido')

    {{-- Totales por estado (CA-HU09-03) --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Pendientes',  'PENDIENTE',  'warning',   'bi-hourglass-split'],
            ['Aprobados',   'APROBADO',   'success',   'bi-check-circle-fill'],
            ['Rechazados',  'RECHAZADO',  'danger',    'bi-x-circle-fill'],
            ['Finalizados', 'FINALIZADO', 'secondary', 'bi-flag-fill'],
        ] as [$titulo, $clave, $color, $icono])
            <div class="col-6 col-lg-3">
                <div class="card kpi-card kpi-{{ $color === 'secondary' ? 'info' : $color }}">
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

    @if ($vencidos > 0 && auth()->user()->tienePermiso('MOVIMIENTOS', 'EDITAR'))
        <div class="alert alert-warning d-flex justify-content-between align-items-center no-imprimir">
            <div>
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>{{ $vencidos }} movimiento(s) aprobado(s) con periodo vencido.</strong>
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

    {{-- Barra de filtros (CA-HU09-01) --}}
    <div class="card mb-3 no-imprimir">
        <div class="card-body">
            <form method="GET" action="{{ route('movimiento.index') }}" class="row g-2 align-items-end">

                <div class="col-12 col-md-3">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="search" class="form-control" id="buscar" name="buscar"
                           value="{{ $filtros['buscar'] }}" placeholder="DNI, nombre o motivo">
                </div>

                <div class="col-6 col-md-2">
                    <label for="tipo_movimiento_id" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo_movimiento_id" name="tipo_movimiento_id">
                        <option value="">Todos</option>
                        @foreach ($tipos as $t)
                            <option value="{{ $t->tipo_movimiento_id }}"
                                @selected((string) $filtros['tipo_movimiento_id'] === (string) $t->tipo_movimiento_id)>
                                {{ str_replace('_', ' ', $t->nombre) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected($filtros['estado'] === $estado)>
                                {{ ucfirst(strtolower($estado)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label for="area_id" class="form-label">Area</label>
                    <select class="form-select" id="area_id" name="area_id">
                        <option value="">Todas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->area_id }}"
                                @selected((string) $filtros['area_id'] === (string) $area->area_id)>
                                {{ $area->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="personal_id" class="form-label">Trabajador</label>
                    <select class="form-select" id="personal_id" name="personal_id">
                        <option value="">Todos</option>
                        @foreach ($personal as $p)
                            <option value="{{ $p->personal_id }}"
                                @selected((string) $filtros['personal_id'] === (string) $p->personal_id)>
                                {{ $p->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="text" class="form-control js-fecha-libre" id="desde" name="desde"
                           value="{{ $filtros['desde'] }}" placeholder="AAAA-MM-DD">
                </div>

                <div class="col-6 col-md-2">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="text" class="form-control js-fecha-libre" id="hasta" name="hasta"
                           value="{{ $filtros['hasta'] }}" placeholder="AAAA-MM-DD">
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Aplicar filtros
                    </button>
                    <a href="{{ route('movimiento.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </a>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                        </button>
                        @if (auth()->user()->tienePermiso('MOVIMIENTOS', 'ESCRIBIR'))
                            <a href="{{ route('movimiento.create') }}" class="btn btn-success">
                                <i class="bi bi-plus-lg me-1"></i> Nuevo movimiento
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Listado --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Resultados</span>
            <span class="badge text-bg-light">{{ $movimientos->total() }} movimiento(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th>Trabajador</th>
                        <th>Area</th>
                        <th>Tipo</th>
                        <th>Periodo</th>
                        <th>Dias</th>
                        <th>Destino</th>
                        <th>Estado</th>
                        <th>Situacion</th>
                        <th class="text-end no-imprimir">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($movimientos as $m)
                    <tr>
                        <td>
                            <a href="{{ route('movimiento.show', $m) }}" class="text-decoration-none fw-semibold">
                                {{ $m->personal?->nombre_completo }}
                            </a>
                            <div class="text-muted font-monospace" style="font-size:.72rem">
                                {{ $m->personal?->dni }}
                            </div>
                        </td>
                        <td class="small">{{ $m->personal?->area?->nombre }}</td>
                        <td class="small">{{ str_replace('_', ' ', $m->tipo?->nombre ?? '') }}</td>
                        <td class="small">{{ $m->periodo }}</td>
                        <td class="small">{{ $m->dias }}</td>
                        <td class="small">{{ $m->establecimientoDestino?->nombre ?? '—' }}</td>
                        <td>
                            <span class="badge badge-estado text-bg-{{ $m->color }}">
                                <i class="bi {{ $m->icono }}"></i> {{ $m->estado }}
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-light">{{ $m->situacion }}</span>
                        </td>
                        <td class="text-end no-imprimir text-nowrap">
                            <a href="{{ route('movimiento.show', $m) }}"
                               class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if ($m->es_editable && auth()->user()->tienePermiso('MOVIMIENTOS', 'ESCRIBIR'))
                                <a href="{{ route('movimiento.edit', $m) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No se encontraron movimientos con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($movimientos->hasPages())
            <div class="card-footer bg-white no-imprimir">
                {{ $movimientos->links() }}
            </div>
        @endif
    </div>

@endsection
