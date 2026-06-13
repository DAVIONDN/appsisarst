{{--
    Capa VISTA - HU-03: Consulta del padron de personal.
    Figuras 1.9 y 1.10 del documento de diseno.
    Filtros por establecimiento, area, cargo, condicion y estado
    (CA-HU03-02) con paginacion del lado del servidor (CA-HU03-05).
--}}
@extends('layouts.app')

@section('titulo', 'Padron de Personal')
@section('subtitulo', 'HU-03 · Consulta del padron con filtros')

@section('contenido')

    {{-- Tarjetas de resumen --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Activos',      $resumen['activos'],     'success', 'bi-people-fill'],
            ['Inactivos',    $resumen['inactivos'],   'danger',  'bi-person-dash'],
            ['Sin horario',  $resumen['sin_horario'], 'warning', 'bi-clock-history'],
            ['Areas',        $resumen['areas'],       'info',    'bi-diagram-3'],
        ] as [$titulo, $valor, $color, $icono])
            <div class="col-6 col-lg-3">
                <div class="card kpi-card kpi-{{ $color }}">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="kpi-titulo">{{ $titulo }}</div>
                            <div class="kpi-valor">{{ $valor }}</div>
                        </div>
                        <i class="bi {{ $icono }} fs-3 text-{{ $color }} opacity-50"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Barra de filtros (CA-HU03-02) --}}
    <div class="card mb-3 no-imprimir">
        <div class="card-body">
            <form method="GET" action="{{ route('personal.index') }}" class="row g-2 align-items-end">

                <div class="col-12 col-md-3">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="search" class="form-control" id="buscar" name="buscar"
                           value="{{ $filtros['buscar'] }}"
                           placeholder="DNI, nombres, apellidos o cargo">
                </div>

                <div class="col-6 col-md-2">
                    <label for="establecimiento_id" class="form-label">Establecimiento</label>
                    <select class="form-select" id="establecimiento_id" name="establecimiento_id">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $establecimiento)
                            <option value="{{ $establecimiento->establecimiento_id }}"
                                @selected((string) $filtros['establecimiento_id'] === (string) $establecimiento->establecimiento_id)>
                                {{ $establecimiento->nombre }}
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

                <div class="col-6 col-md-2">
                    <label for="cargo" class="form-label">Cargo</label>
                    <input type="text" class="form-control" id="cargo" name="cargo"
                           value="{{ $filtros['cargo'] }}" placeholder="Ej.: Enfermero">
                </div>

                <div class="col-6 col-md-2">
                    <label for="condicion_laboral" class="form-label">Condicion</label>
                    <select class="form-select" id="condicion_laboral" name="condicion_laboral">
                        <option value="">Todas</option>
                        @foreach ($condiciones as $condicion)
                            <option value="{{ $condicion }}"
                                @selected($filtros['condicion_laboral'] === $condicion)>{{ $condicion }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="ACTIVO"   @selected($filtros['estado'] === 'ACTIVO')>Activo</option>
                        <option value="INACTIVO" @selected($filtros['estado'] === 'INACTIVO')>Inactivo</option>
                        <option value=""         @selected($filtros['estado'] === '')>Todos</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Aplicar filtros
                    </button>
                    <a href="{{ route('personal.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </a>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                        </button>
                        @if (auth()->user()->tienePermiso('PADRON', 'ESCRIBIR'))
                            <a href="{{ route('personal.create') }}" class="btn btn-success">
                                <i class="bi bi-plus-lg me-1"></i> Nuevo personal
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
            <span class="badge text-bg-light">{{ $personal->total() }} registro(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th>DNI</th>
                        <th>Apellidos y nombres</th>
                        <th>Cargo</th>
                        <th>Condicion</th>
                        <th>Area / Establecimiento</th>
                        <th>Horario</th>
                        <th>Ingreso</th>
                        <th>Estado</th>
                        <th class="text-end no-imprimir">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($personal as $trabajador)
                    <tr>
                        <td class="font-monospace">{{ $trabajador->dni }}</td>
                        <td>
                            <a href="{{ route('personal.show', $trabajador) }}"
                               class="text-decoration-none fw-semibold">
                                {{ $trabajador->nombre_completo }}
                            </a>
                            @if ($trabajador->correo)
                                <div class="small text-muted">{{ $trabajador->correo }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $trabajador->cargo }}</td>
                        <td><span class="badge text-bg-secondary">{{ $trabajador->condicion_laboral }}</span></td>
                        <td class="small">
                            {{ $trabajador->area?->nombre }}
                            <div class="text-muted">{{ $trabajador->area?->establecimiento?->nombre }}</div>
                        </td>
                        <td class="small">
                            @if ($trabajador->horario)
                                {{ $trabajador->horario->nombre }}
                            @else
                                <span class="badge text-bg-warning">Sin asignar</span>
                            @endif
                        </td>
                        <td class="small">{{ $trabajador->fecha_ingreso?->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge badge-estado text-bg-{{ $trabajador->es_activo ? 'success' : 'danger' }}">
                                {{ $trabajador->estado }}
                            </span>
                        </td>
                        <td class="text-end no-imprimir text-nowrap">
                            <a href="{{ route('personal.show', $trabajador) }}"
                               class="btn btn-sm btn-outline-primary" title="Historial (HU-18)">
                                <i class="bi bi-clock-history"></i>
                            </a>

                            @if (auth()->user()->tienePermiso('PADRON', 'EDITAR'))
                                <a href="{{ route('personal.edit', $trabajador) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Editar (HU-02)">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif

                            @if (auth()->user()->tienePermiso('PADRON', 'ELIMINAR'))
                                @if ($trabajador->es_activo)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger js-desactivar"
                                            title="Desactivar (HU-04)"
                                            data-form="#form-baja-{{ $trabajador->personal_id }}"
                                            data-nombre="{{ $trabajador->nombre_completo }} · DNI {{ $trabajador->dni }}">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                    <form id="form-baja-{{ $trabajador->personal_id }}" class="d-none"
                                          method="POST" action="{{ route('personal.desactivar', $trabajador) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="motivo_baja" value="">
                                    </form>
                                @else
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success js-reactivar"
                                            title="Reactivar"
                                            data-form="#form-alta-{{ $trabajador->personal_id }}"
                                            data-nombre="{{ $trabajador->nombre_completo }} · DNI {{ $trabajador->dni }}">
                                        <i class="bi bi-person-check"></i>
                                    </button>
                                    <form id="form-alta-{{ $trabajador->personal_id }}" class="d-none"
                                          method="POST" action="{{ route('personal.reactivar', $trabajador) }}">
                                        @csrf @method('PATCH')
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No se encontro personal con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($personal->hasPages())
            <div class="card-footer bg-white no-imprimir">
                {{ $personal->links() }}
            </div>
        @endif
    </div>

@endsection
