{{--
    Capa VISTA - Modulo de Reportes Administrativos.
    Figuras 5.7 y 5.8 del documento de diseno: los tres reportes en
    pestanas, con su barra de filtros, los botones de generacion y
    exportacion, la tabla de resultados y las tarjetas KPI.
--}}
@extends('layouts.app')

@section('titulo', 'Reportes Administrativos')
@section('subtitulo', 'HU-13, HU-14 y HU-15 · Sprint 5')

@section('contenido')

    @if ($areaRestringida !== null)
        <div class="alert alert-info py-2 small no-imprimir">
            <i class="bi bi-info-circle-fill me-1"></i>
            Su rol limita los reportes al personal de <strong>su area</strong>.
        </div>
    @endif

    {{-- Pestañas de los tres reportes --}}
    <ul class="nav nav-tabs mb-3 no-imprimir">
        @foreach ([
            ['personal',    'HU-13 · Personal',    'bi-people-fill'],
            ['asistencia',  'HU-14 · Asistencia',  'bi-calendar-check'],
            ['movimientos', 'HU-15 · Movimientos', 'bi-arrow-left-right'],
        ] as [$clave, $etiqueta, $icono])
            <li class="nav-item">
                <button class="nav-link {{ $pestana === $clave ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#panel-{{ $clave }}" type="button">
                    <i class="bi {{ $icono }} me-1"></i> {{ $etiqueta }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content no-imprimir">

        {{-- ============ HU-13: PERSONAL ============ --}}
        <div class="tab-pane fade {{ $pestana === 'personal' ? 'show active' : '' }}" id="panel-personal">
            <form method="GET" action="{{ route('reporte.personal') }}" class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-people-fill me-1"></i> Reporte del Padron de Personal
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label for="p_establecimiento" class="form-label">Establecimiento</label>
                            <select class="form-select" id="p_establecimiento" name="establecimiento_id">
                                <option value="">Todos</option>
                                @foreach ($establecimientos as $e)
                                    <option value="{{ $e->establecimiento_id }}"
                                        @selected(($filtros['establecimiento_id'] ?? '') == $e->establecimiento_id)>
                                        {{ $e->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="p_area" class="form-label">Area</label>
                            <select class="form-select" id="p_area" name="area_id">
                                <option value="">Todas</option>
                                @foreach ($areas as $a)
                                    <option value="{{ $a->area_id }}"
                                        @selected(($filtros['area_id'] ?? '') == $a->area_id)>{{ $a->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="p_cargo" class="form-label">Cargo</label>
                            <input type="text" class="form-control" id="p_cargo" name="cargo"
                                   value="{{ $filtros['cargo'] ?? '' }}" placeholder="Ej.: Enfermero">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="p_condicion" class="form-label">Condicion</label>
                            <select class="form-select" id="p_condicion" name="condicion_laboral">
                                <option value="">Todas</option>
                                @foreach ($condiciones as $c)
                                    <option value="{{ $c }}"
                                        @selected(($filtros['condicion_laboral'] ?? '') === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="p_estado" class="form-label">Estado</label>
                            <select class="form-select" id="p_estado" name="estado">
                                <option value="">Todos</option>
                                <option value="ACTIVO" @selected(($filtros['estado'] ?? '') === 'ACTIVO')>Activo</option>
                                <option value="INACTIVO" @selected(($filtros['estado'] ?? '') === 'INACTIVO')>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    @include('reporte._acciones', ['ruta' => route('reporte.personal')])
                </div>
            </form>
        </div>

        {{-- ============ HU-14: ASISTENCIA ============ --}}
        <div class="tab-pane fade {{ $pestana === 'asistencia' ? 'show active' : '' }}" id="panel-asistencia">
            <form method="GET" action="{{ route('reporte.asistencia') }}" class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-calendar-check me-1"></i> Reporte de Asistencia
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-2">
                            <label for="a_desde" class="form-label obligatorio">Desde</label>
                            <input type="date" class="form-control" id="a_desde" name="desde"
                                   value="{{ $filtros['desde'] ?? now()->startOfMonth()->toDateString() }}" required>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="a_hasta" class="form-label obligatorio">Hasta</label>
                            <input type="date" class="form-control" id="a_hasta" name="hasta"
                                   value="{{ $filtros['hasta'] ?? now()->toDateString() }}" required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="a_area" class="form-label">Area</label>
                            <select class="form-select" id="a_area" name="area_id">
                                <option value="">Todas</option>
                                @foreach ($areas as $a)
                                    <option value="{{ $a->area_id }}"
                                        @selected(($filtros['area_id'] ?? '') == $a->area_id)>{{ $a->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="a_personal" class="form-label">Trabajador</label>
                            <select class="form-select" id="a_personal" name="personal_id">
                                <option value="">Todos</option>
                                @foreach ($personal as $p)
                                    <option value="{{ $p->personal_id }}"
                                        @selected(($filtros['personal_id'] ?? '') == $p->personal_id)>
                                        {{ $p->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="a_estado" class="form-label">Estado</label>
                            <select class="form-select" id="a_estado" name="estado">
                                <option value="">Todos</option>
                                @foreach ($estadosAsistencia as $e)
                                    <option value="{{ $e }}" @selected(($filtros['estado'] ?? '') === $e)>
                                        {{ ucfirst(strtolower($e)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @include('reporte._acciones', ['ruta' => route('reporte.asistencia')])
                </div>
            </form>
        </div>

        {{-- ============ HU-15: MOVIMIENTOS ============ --}}
        <div class="tab-pane fade {{ $pestana === 'movimientos' ? 'show active' : '' }}" id="panel-movimientos">
            <form method="GET" action="{{ route('reporte.movimientos') }}" class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-arrow-left-right me-1"></i> Reporte de Movimientos Institucionales
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-2">
                            <label for="m_desde" class="form-label">Desde</label>
                            <input type="date" class="form-control" id="m_desde" name="desde"
                                   value="{{ $filtros['desde'] ?? '' }}">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="m_hasta" class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="m_hasta" name="hasta"
                                   value="{{ $filtros['hasta'] ?? '' }}">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="m_tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="m_tipo" name="tipo_movimiento_id">
                                <option value="">Todos</option>
                                @foreach ($tipos as $t)
                                    <option value="{{ $t->tipo_movimiento_id }}"
                                        @selected(($filtros['tipo_movimiento_id'] ?? '') == $t->tipo_movimiento_id)>
                                        {{ str_replace('_', ' ', $t->nombre) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="m_area" class="form-label">Area</label>
                            <select class="form-select" id="m_area" name="area_id">
                                <option value="">Todas</option>
                                @foreach ($areas as $a)
                                    <option value="{{ $a->area_id }}"
                                        @selected(($filtros['area_id'] ?? '') == $a->area_id)>{{ $a->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="m_personal" class="form-label">Trabajador</label>
                            <select class="form-select" id="m_personal" name="personal_id">
                                <option value="">Todos</option>
                                @foreach ($personal as $p)
                                    <option value="{{ $p->personal_id }}"
                                        @selected(($filtros['personal_id'] ?? '') == $p->personal_id)>
                                        {{ $p->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="m_estado" class="form-label">Estado</label>
                            <select class="form-select" id="m_estado" name="estado">
                                <option value="">Todos</option>
                                @foreach ($estadosMovimiento as $e)
                                    <option value="{{ $e }}" @selected(($filtros['estado'] ?? '') === $e)>
                                        {{ ucfirst(strtolower($e)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @include('reporte._acciones', ['ruta' => route('reporte.movimientos')])
                </div>
            </form>
        </div>
    </div>

    {{-- ============ RESULTADO ============ --}}
    @if ($reporte !== null)
        @include('reporte._resultado', ['reporte' => $reporte])
    @else
        <div class="card no-imprimir">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-file-earmark-bar-graph display-4 d-block mb-3 opacity-50"></i>
                <p class="mb-0">
                    Elija una pestana, configure los filtros y pulse <strong>Generar reporte</strong>.
                </p>
            </div>
        </div>
    @endif

@endsection
