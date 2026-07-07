{{-- Capa VISTA - HU-08: registro de un movimiento (Figuras 3.9 y 3.10). --}}
@extends('layouts.app')

@section('titulo', 'Nuevo movimiento')
@section('subtitulo', 'HU-08 · Registro de movimiento institucional')

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('movimiento.index') }}">Movimientos</a></li>
            <li class="breadcrumb-item active">Nuevo movimiento</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('movimiento.store') }}" novalidate>
        @csrf

        <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-left-right me-1"></i> Datos del movimiento
            </div>
            <div class="card-body">
                @include('movimiento._formulario', ['movimiento' => null])
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('movimiento.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Registrar movimiento
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
