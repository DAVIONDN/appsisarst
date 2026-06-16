{{-- Capa VISTA - HU-16: edicion de un horario del catalogo. --}}
@extends('layouts.app')

@section('titulo', 'Editar horario')
@section('subtitulo', 'HU-16 · '.$horario->nombre)

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('horario.index') }}">Horarios</a></li>
            <li class="breadcrumb-item active">{{ $horario->nombre }}</li>
        </ol>
    </nav>

    @if ($horario->personal()->count() > 0)
        <div class="alert alert-info small">
            <i class="bi bi-info-circle-fill me-1"></i>
            Este horario esta asignado a <strong>{{ $horario->personal()->count() }}</strong>
            trabajador(es). Cambiar la hora de entrada o la tolerancia modifica como se
            evaluaran sus <strong>proximas</strong> marcaciones; las jornadas ya registradas
            no se recalculan.
        </div>
    @endif

    <form method="POST" action="{{ route('horario.update', $horario) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-1"></i> Datos del horario</span>
                <span class="badge text-bg-{{ $horario->activo ? 'success' : 'secondary' }}">
                    {{ $horario->activo ? 'ACTIVO' : 'INACTIVO' }}
                </span>
            </div>
            <div class="card-body">
                @include('horario._formulario')
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Hora limite actual sin tardanza:
                    <strong class="font-monospace">{{ $horario->hora_limite }}</strong>
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('horario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
