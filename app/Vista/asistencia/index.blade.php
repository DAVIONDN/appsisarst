{{--
    Capa VISTA - HU-06: Consulta de asistencia por periodo.
    Figuras 2.10 y 2.11 del documento de diseno.
    Filtros por rango de fechas, personal, area, cargo y estado
    (CA-HU06-01 y CA-HU06-02), con los colores semanticos del Sprint 2.
--}}
@extends('layouts.app')

@section('titulo', 'Control de Asistencia')
@section('subtitulo', 'HU-06 · Consulta por periodo')

@section('contenido')

    {{-- Resumen del periodo filtrado (CA-HU06-02) --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Registros',    $resumen['total'],        '',        'bi-list-check'],
            ['Puntuales',    $resumen['puntuales'],    'success', 'bi-check-circle-fill'],
            ['Tardanzas',    $resumen['tardanzas'],    'warning', 'bi-clock-fill'],
            ['Faltas',       $resumen['faltas'],       'danger',  'bi-x-circle-fill'],
            ['Justificados', $resumen['justificados'], 'info',    'bi-file-earmark-text-fill'],
        ] as [$titulo, $valor, $color, $icono])
            <div class="col-6 col-lg">
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

        <div class="col-6 col-lg">
            <div class="card kpi-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-titulo">Cumplimiento</div>
                    <div class="kpi-valor">{{ $resumen['cumplimiento'] }}%</div>
                    <div class="small text-muted">{{ $resumen['minutos_tardanza'] }} min de tardanza</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de filtros (CA-HU06-01) --}}
    <div class="card mb-3 no-imprimir">
        <div class="card-body">
            <form method="GET" action="{{ route('asistencia.index') }}" class="row g-2 align-items-end">

                <div class="col-6 col-md-2">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="text" class="form-control js-fecha" id="desde" name="desde"
                           value="{{ $filtros['desde'] }}">
                </div>

                <div class="col-6 col-md-2">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="text" class="form-control js-fecha" id="hasta" name="hasta"
                           value="{{ $filtros['hasta'] }}">
                </div>

                <div class="col-12 col-md-3">
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

                <div class="col-6 col-md-1">
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

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Aplicar filtros
                    </button>
                    <a href="{{ route('asistencia.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </a>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                        </button>
                        @if (auth()->user()->tienePermiso('ASISTENCIA', 'ESCRIBIR'))
                            <a href="{{ route('asistencia.create') }}" class="btn btn-success">
                                <i class="bi bi-clock me-1"></i> Marcar asistencia
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
            <span>
                Periodo
                {{ \Illuminate\Support\Carbon::parse($filtros['desde'])->format('d/m/Y') }} –
                {{ \Illuminate\Support\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}
            </span>
            <span class="badge text-bg-light">{{ $registros->total() }} registro(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Trabajador</th>
                        <th>Area</th>
                        <th>Horario</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas</th>
                        <th>Estado</th>
                        <th>Tardanza</th>
                        <th>Origen</th>
                        <th class="text-end no-imprimir">Accion</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($registros as $a)
                    <tr>
                        <td class="small">{{ $a->fecha?->format('d/m/Y') }}</td>
                        <td>
                            <span class="fw-semibold">{{ $a->personal?->nombre_completo }}</span>
                            <div class="text-muted font-monospace" style="font-size:.72rem">
                                {{ $a->personal?->dni }}
                            </div>
                        </td>
                        <td class="small">{{ $a->personal?->area?->nombre }}</td>
                        <td class="small">
                            {{ $a->personal?->horario?->nombre ?? '—' }}
                        </td>
                        <td class="font-monospace">{{ $a->entrada_corta }}</td>
                        <td class="font-monospace">{{ $a->salida_corta }}</td>
                        <td class="small">{{ $a->horas_trabajadas !== null ? $a->horas_trabajadas.' h' : '—' }}</td>
                        <td>
                            <span class="badge badge-estado text-bg-{{ $a->color }}">
                                <i class="bi {{ $a->icono }}"></i> {{ $a->estado }}
                            </span>
                        </td>
                        <td class="small">{{ $a->minutos_tardanza > 0 ? $a->minutos_tardanza.' min' : '—' }}</td>
                        <td>
                            <span class="badge text-bg-light">{{ $a->origen }}</span>
                        </td>
                        <td class="text-end no-imprimir">
                            @if (auth()->user()->tienePermiso('ASISTENCIA', 'ESCRIBIR'))
                                <a href="{{ route('asistencia.edit', $a) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Corregir jornada">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No se encontraron registros de asistencia con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="card-footer bg-white no-imprimir">
                {{ $registros->links() }}
            </div>
        @endif
    </div>

@endsection
