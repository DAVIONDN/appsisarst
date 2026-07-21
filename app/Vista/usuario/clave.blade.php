{{--
    Capa VISTA - Restablecimiento administrativo de la contrasena.
    Es la via para cuando el usuario no puede usar la recuperacion por
    correo (HU-17), por ejemplo si su correo institucional cambio.
--}}
@extends('layouts.app')

@section('titulo', 'Restablecer contrasena')
@section('subtitulo', $usuario->username.' · '.$usuario->personal?->nombre_completo)

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('usuario.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('usuario.show', $usuario) }}">{{ $usuario->username }}</a>
            </li>
            <li class="breadcrumb-item active">Restablecer contrasena</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">

            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Esta accion sobrescribe la contrasena de otra persona. Preferible que el propio
                usuario la recupere desde <strong>«Olvide mi contrasena»</strong>; use esta via
                solo si no tiene acceso a su correo institucional.
                Todo restablecimiento queda registrado en la auditoria.
            </div>

            <form method="POST" action="{{ route('usuario.clave', $usuario) }}" novalidate autocomplete="off">
                @csrf
                @method('PATCH')

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-key me-1"></i> Contrasena nueva
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="password" class="form-label obligatorio">Contrasena</label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required autocomplete="new-password">
                            <div class="form-text">Minimo 8 caracteres, con letras y numeros.</div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="password_confirmation" class="form-label obligatorio">
                                Confirmar contrasena
                            </label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation"
                                   required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Se reinicia el contador de intentos y se invalida cualquier enlace vigente.
                        </small>
                        <div class="d-flex gap-2">
                            <a href="{{ route('usuario.show', $usuario) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Restablecer
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
