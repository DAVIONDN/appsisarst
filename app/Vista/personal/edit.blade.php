{{--
    Capa VISTA - HU-02: Edicion de datos del personal (Figuras 1.13 y 1.14).
    El sistema muestra los datos actuales antes de editar (CA-HU02-02).
--}}
@extends('layouts.app')

@section('titulo', 'Editar personal')
@section('subtitulo', 'HU-02 · '.$personal->nombre_completo)

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('personal.index') }}">Padron</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('personal.show', $personal) }}">{{ $personal->nombre_completo }}</a>
            </li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>

    @unless ($personal->es_activo)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Este trabajador esta <strong>INACTIVO</strong>. Motivo de la baja:
            {{ $personal->motivo_baja ?: 'no registrado' }}.
        </div>
    @endunless

    <form method="POST" action="{{ route('personal.update', $personal) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-1"></i> Datos actuales del trabajador</span>
                <span class="badge text-bg-{{ $personal->es_activo ? 'success' : 'danger' }}">
                    {{ $personal->estado }}
                </span>
            </div>
            <div class="card-body">
                @include('personal._formulario')

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-light border small mb-0">
                            <strong>Registro:</strong>
                            creado el {{ $personal->created_at?->format('d/m/Y H:i') }} ·
                            ultima modificacion {{ $personal->updated_at?->format('d/m/Y H:i') }}.
                            El estado del trabajador solo se cambia desde la accion de
                            desactivacion (HU-04), no desde este formulario.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</small>
                <div class="d-flex gap-2">
                    <a href="{{ route('personal.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
