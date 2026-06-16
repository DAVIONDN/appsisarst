{{--
    Capa VISTA - HU-16: Catalogo de horarios de trabajo.
    Incluye la advertencia del personal sin horario asignado, que el
    criterio CA-HU16-03 exige mostrar porque no puede ser evaluado.
--}}
@extends('layouts.app')

@section('titulo', 'Horarios de Trabajo')
@section('subtitulo', 'HU-16 · Catalogo y asignacion')

@section('contenido')

    <div class="card mb-3 no-imprimir">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <form method="GET" action="{{ route('horario.index') }}" class="d-flex gap-2 flex-grow-1">
                <div class="flex-grow-1" style="max-width:320px">
                    <label for="buscar" class="form-label">Buscar horario</label>
                    <input type="search" class="form-control" id="buscar" name="buscar"
                           value="{{ $buscar }}" placeholder="Nombre del horario">
                </div>
                <div class="align-self-end d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    <a href="{{ route('horario.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>

            @if (auth()->user()->tienePermiso('ASISTENCIA', 'ESCRIBIR'))
                <a href="{{ route('horario.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo horario
                </a>
            @endif
        </div>
    </div>

    {{-- Catalogo --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Catalogo de horarios</span>
            <span class="badge text-bg-light">{{ $horarios->count() }} horario(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Tolerancia</th>
                        <th>Hora limite</th>
                        <th>Dias laborales</th>
                        <th>Asignados</th>
                        <th>Estado</th>
                        <th class="text-end no-imprimir">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($horarios as $h)
                    <tr>
                        <td class="fw-semibold">{{ $h->nombre }}</td>
                        <td class="font-monospace">{{ $h->horaCorta('hora_entrada') }}</td>
                        <td class="font-monospace">{{ $h->horaCorta('hora_salida') }}</td>
                        <td>{{ $h->tolerancia_min }} min</td>
                        <td class="font-monospace">{{ $h->hora_limite }}</td>
                        <td class="small">{{ $h->dias_legibles }}</td>
                        <td>
                            <span class="badge text-bg-{{ $h->asignados_count > 0 ? 'primary' : 'light' }}">
                                {{ $h->asignados_count }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-estado text-bg-{{ $h->activo ? 'success' : 'secondary' }}">
                                {{ $h->activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="text-end no-imprimir text-nowrap">
                            @if (auth()->user()->tienePermiso('ASISTENCIA', 'ESCRIBIR'))
                                @if ($h->activo)
                                    <a href="{{ route('horario.asignar.form', $h) }}"
                                       class="btn btn-sm btn-outline-primary" title="Asignar a personal">
                                        <i class="bi bi-people"></i>
                                    </a>
                                @endif
                                <a href="{{ route('horario.edit', $h) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if ($h->activo)
                                    <button type="button" class="btn btn-sm btn-outline-danger js-confirmar"
                                            title="Desactivar"
                                            data-form="#form-baja-{{ $h->horario_id }}"
                                            data-titulo="¿Desactivar el horario?"
                                            data-texto="{{ $h->nombre }}">
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                    <form id="form-baja-{{ $h->horario_id }}" class="d-none"
                                          method="POST" action="{{ route('horario.desactivar', $h) }}">
                                        @csrf @method('PATCH')
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-success js-confirmar"
                                            title="Reactivar"
                                            data-form="#form-alta-{{ $h->horario_id }}"
                                            data-titulo="¿Reactivar el horario?"
                                            data-texto="{{ $h->nombre }}">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <form id="form-alta-{{ $h->horario_id }}" class="d-none"
                                          method="POST" action="{{ route('horario.reactivar', $h) }}">
                                        @csrf @method('PATCH')
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No hay horarios registrados.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CA-HU16-03: personal que el sistema no puede evaluar --}}
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                Personal activo sin horario asignado
            </span>
            <span class="badge text-bg-{{ $sinHorario->isEmpty() ? 'success' : 'warning' }}">
                {{ $sinHorario->count() }}
            </span>
        </div>

        @if ($sinHorario->isEmpty())
            <div class="card-body">
                <p class="text-success small mb-0">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Todo el personal activo tiene horario asignado. El cierre de jornada
                    puede evaluar a todos.
                </p>
            </div>
        @else
            <div class="card-body pb-0">
                <p class="small text-muted">
                    Sin horario asignado el sistema no evalua tardanzas ni genera faltas
                    automaticas para estos trabajadores (CA-HU16-03). Asigneles un horario
                    desde el boton <i class="bi bi-people"></i> del catalogo.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 tabla-padron">
                    <thead class="table-light">
                        <tr><th>DNI</th><th>Trabajador</th><th>Cargo</th><th>Area</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($sinHorario as $p)
                        <tr>
                            <td class="font-monospace">{{ $p->dni }}</td>
                            <td>{{ $p->nombre_completo }}</td>
                            <td class="small">{{ $p->cargo }}</td>
                            <td class="small">{{ $p->area?->nombre }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
