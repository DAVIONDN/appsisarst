{{-- Capa VISTA - HU-16 / CA-HU16-01: alta de un horario de trabajo. --}}
@extends('layouts.app')

@section('titulo', 'Nuevo horario')
@section('subtitulo', 'HU-16 · Registro en el catalogo de horarios')

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('horario.index') }}">Horarios</a></li>
            <li class="breadcrumb-item active">Nuevo horario</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('horario.store') }}" novalidate>
        @csrf

        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-1"></i> Datos del horario
            </div>
            <div class="card-body">
                @include('horario._formulario', ['horario' => null])
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('horario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar horario
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
