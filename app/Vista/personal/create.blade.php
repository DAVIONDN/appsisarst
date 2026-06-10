{{-- Capa VISTA - HU-01: Registro de nuevo personal (Figuras 1.11 y 1.12). --}}
@extends('layouts.app')

@section('titulo', 'Registrar personal')
@section('subtitulo', 'HU-01 · Alta en el padron de la Red de Salud Tayacaja')

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('personal.index') }}">Padron</a></li>
            <li class="breadcrumb-item active">Nuevo registro</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('personal.store') }}" novalidate>
        @csrf

        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus me-1"></i> Datos del trabajador
            </div>
            <div class="card-body">
                @include('personal._formulario', ['personal' => null])
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</small>
                <div class="d-flex gap-2">
                    <a href="{{ route('personal.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar registro
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
