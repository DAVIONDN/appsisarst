{{--
    Capa VISTA - HU-16 / CA-HU16-02: asignacion de un horario a uno o
    varios trabajadores. Un mismo horario lo comparten varios: por eso la
    seleccion es multiple.
--}}
@extends('layouts.app')

@section('titulo', 'Asignar horario')
@section('subtitulo', 'HU-16 · '.$horario->etiqueta)

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('horario.index') }}">Horarios</a></li>
            <li class="breadcrumb-item active">Asignar {{ $horario->nombre }}</li>
        </ol>
    </nav>

    <div class="alert alert-primary d-flex align-items-center gap-3 border-0">
        <i class="bi bi-clock-history fs-3"></i>
        <div class="small">
            <strong>{{ $horario->nombre }}</strong> ·
            entrada {{ $horario->horaCorta('hora_entrada') }},
            salida {{ $horario->horaCorta('hora_salida') }},
            tolerancia {{ $horario->tolerancia_min }} min ·
            {{ $horario->dias_legibles }}<br>
            Hora limite sin tardanza: <strong class="font-monospace">{{ $horario->hora_limite }}</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('horario.asignar', $horario) }}">
        @csrf

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Seleccione a los trabajadores</span>
                <div class="d-flex gap-2 align-items-center">
                    <input type="search" class="form-control form-control-sm js-filtrar-tabla"
                           data-tabla="#tabla-asignacion" placeholder="Filtrar..." style="width:220px">
                    <button type="button" class="btn btn-sm btn-outline-secondary js-marcar-todos"
                            data-tabla="#tabla-asignacion">
                        Marcar visibles
                    </button>
                </div>
            </div>

            @error('personal_ids')
                <div class="px-3 pt-3"><div class="alert alert-danger py-2 small mb-0">{{ $message }}</div></div>
            @enderror

            <div class="table-responsive" style="max-height:60vh">
                <table class="table table-hover align-middle mb-0 tabla-padron" id="tabla-asignacion">
                    <thead class="table-light position-sticky top-0">
                        <tr>
                            <th style="width:3rem"></th>
                            <th>DNI</th>
                            <th>Trabajador</th>
                            <th>Cargo</th>
                            <th>Area</th>
                            <th>Horario actual</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($personal as $p)
                        @php($yaLoTiene = (int) $p->horario_id === (int) $horario->horario_id)
                        <tr class="{{ $yaLoTiene ? 'table-success' : '' }}">
                            <td>
                                <input class="form-check-input" type="checkbox"
                                       name="personal_ids[]" value="{{ $p->personal_id }}"
                                       id="p{{ $p->personal_id }}" @checked($yaLoTiene)>
                            </td>
                            <td class="font-monospace">{{ $p->dni }}</td>
                            <td><label class="mb-0" for="p{{ $p->personal_id }}">{{ $p->nombre_completo }}</label></td>
                            <td class="small">{{ $p->cargo }}</td>
                            <td class="small">
                                {{ $p->area?->nombre }}
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $p->area?->establecimiento?->nombre }}
                                </div>
                            </td>
                            <td class="small">
                                @if ($p->horario === null)
                                    <span class="badge text-bg-warning">Sin asignar</span>
                                @elseif ($yaLoTiene)
                                    <span class="badge text-bg-success">{{ $p->horario->nombre }}</span>
                                @else
                                    <span class="badge text-bg-light">{{ $p->horario->nombre }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            No hay personal activo en el padron.
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Las filas en verde ya tienen este horario. Desmarcarlas aqui no lo retira:
                    para quitarlo use la accion correspondiente en la ficha del trabajador.
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('horario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-all me-1"></i> Asignar horario
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
