{{-- Capa VISTA - HU-11 / CA-HU11-02: edicion de la cuenta. --}}
@extends('layouts.app')

@section('titulo', 'Editar cuenta')
@section('subtitulo', 'HU-11 · '.$usuario->username)

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('usuario.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('usuario.show', $usuario) }}">{{ $usuario->username }}</a>
            </li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>

    @if ($usuario->estaBloqueado())
        <div class="alert alert-danger small">
            <i class="bi bi-lock-fill me-1"></i>
            Esta cuenta esta <strong>BLOQUEADA</strong> tras
            {{ $usuario->intentos_fallidos }} intentos fallidos. Desbloquearla desde la ficha
            reinicia el contador.
        </div>
    @endif

    <form method="POST" action="{{ route('usuario.update', $usuario) }}" novalidate autocomplete="off">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-1"></i> Datos de la cuenta</span>
                <span class="badge text-bg-{{ $usuario->color }}">{{ $usuario->estado }}</span>
            </div>
            <div class="card-body">
                @include('usuario._formulario')
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Creada el {{ $usuario->created_at?->format('d/m/Y H:i') }} ·
                    Ultimo acceso: {{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'nunca' }}
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('usuario.show', $usuario) }}" class="btn btn-outline-secondary">
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
