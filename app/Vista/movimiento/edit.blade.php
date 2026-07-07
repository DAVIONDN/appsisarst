{{--
    Capa VISTA - HU-08: edicion de un movimiento PENDIENTE.
    Una vez aprobado o rechazado el movimiento es un acto administrativo y
    ya no admite cambios: solo transiciones de estado.
--}}
@extends('layouts.app')

@section('titulo', 'Editar movimiento')
@section('subtitulo', 'HU-08 · '.$movimiento->personal?->nombre_completo)

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('movimiento.index') }}">Movimientos</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('movimiento.show', $movimiento) }}">#{{ $movimiento->movimiento_id }}</a>
            </li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle-fill me-1"></i>
        Este movimiento esta <strong>PENDIENTE</strong>, por eso admite cambios. Al aprobarlo o
        rechazarlo quedara cerrado a edicion.
    </div>

    <form method="POST" action="{{ route('movimiento.update', $movimiento) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-1"></i> Datos del movimiento</span>
                <span class="badge text-bg-{{ $movimiento->color }}">{{ $movimiento->estado }}</span>
            </div>
            <div class="card-body">
                @include('movimiento._formulario')
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Registrado el {{ $movimiento->created_at?->format('d/m/Y H:i') }}
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('movimiento.show', $movimiento) }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
