{{-- Capa VISTA - HU-11 / CA-HU11-01 (Figuras 4.16 y 4.17). --}}
@extends('layouts.app')

@section('titulo', 'Nueva cuenta de usuario')
@section('subtitulo', 'HU-11 · Creacion de usuario y asignacion de rol')

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('usuario.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item active">Nueva cuenta</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('usuario.store') }}" novalidate autocomplete="off">
        @csrf

        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus me-1"></i> Datos de la cuenta
            </div>
            <div class="card-body">
                @include('usuario._formulario', ['usuario' => null])
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    La contrasena se guarda cifrada con bcrypt; nunca queda en texto claro (RNF-02).
                </small>
                <div class="d-flex gap-2">
                    <a href="{{ route('usuario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Crear cuenta
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
