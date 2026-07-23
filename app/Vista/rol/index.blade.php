{{--
    Capa VISTA - HU-12: matriz de roles y permisos.
    CA-HU12-03: los permisos se ven y se configuran por modulo y por tipo
    de accion (leer, escribir, editar, eliminar).
--}}
@extends('layouts.app')

@section('titulo', 'Roles y Permisos')
@section('subtitulo', 'HU-12 · Matriz de acceso del sistema')

@section('contenido')

    <div class="alert alert-light border small">
        <i class="bi bi-info-circle me-1"></i>
        Esta matriz es la que aplica el sistema en cada peticion (CA-HU12-02).
        Un permiso marcado habilita esa accion en ese modulo para todos los usuarios
        que tengan el rol.
    </div>

    {{-- Resumen por rol --}}
    <div class="row g-3 mb-3">
        @foreach ($roles as $rol)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h6 mb-0">{{ $rol->nombre }}</h2>
                            <span class="badge text-bg-{{ $rol->activo ? 'success' : 'secondary' }}">
                                {{ $rol->activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </div>
                        <p class="small text-muted">{{ $rol->descripcion }}</p>
                        <div class="d-flex gap-3 small">
                            <span><strong>{{ $rol->usuarios_count }}</strong> cuenta(s)</span>
                            <span><strong>{{ $rol->permisos->count() }}</strong> permiso(s)</span>
                        </div>
                    </div>
                    @if (auth()->user()->tienePermiso('USUARIOS', 'EDITAR'))
                        <div class="card-footer bg-white">
                            <a href="{{ route('rol.edit', $rol) }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-sliders me-1"></i> Configurar permisos
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Matriz completa --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Matriz completa: modulo × accion × rol</span>
            <button type="button" class="btn btn-sm btn-outline-secondary no-imprimir" onclick="window.print()">
                <i class="bi bi-printer"></i>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle">Modulo</th>
                        <th rowspan="2" class="align-middle">Accion</th>
                        @foreach ($roles as $rol)
                            <th class="text-center">{{ $rol->nombre }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($roles as $rol)
                            <th class="text-center fw-normal" style="font-size:.7rem">
                                {{ $rol->usuarios_count }} cuenta(s)
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach ($matriz as $modulo => $porAccion)
                    @foreach ($acciones as $i => $accion)
                        @php($permiso = $porAccion[$accion] ?? null)
                        @continue($permiso === null)
                        <tr>
                            @if ($i === 0 || $loop->first)
                                <td class="fw-semibold">{{ $modulo }}</td>
                            @else
                                <td class="text-muted">{{ $modulo }}</td>
                            @endif
                            <td><span class="badge text-bg-light">{{ $accion }}</span></td>
                            @foreach ($roles as $rol)
                                <td class="text-center">
                                    @if ($rol->permisos->contains('permiso_id', $permiso->permiso_id))
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-dash text-muted opacity-50"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">
            El catalogo de modulos y acciones lo define la tabla <code>permiso</code>, que carga
            el script de creacion de la base. El sistema no inventa permisos: solo los asigna.
        </div>
    </div>

@endsection
