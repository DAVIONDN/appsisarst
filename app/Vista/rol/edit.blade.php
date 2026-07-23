{{--
    Capa VISTA - HU-12 / CA-HU12-03: configuracion de los permisos de un
    rol como grilla modulo x accion. Cada casilla es un permiso concreto
    del catalogo; las combinaciones que el catalogo no define aparecen en
    gris porque no existen.
--}}
@extends('layouts.app')

@section('titulo', 'Permisos del rol '.$rol->nombre)
@section('subtitulo', 'HU-12 · Configuracion por modulo y accion')

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('rol.index') }}">Roles y permisos</a></li>
            <li class="breadcrumb-item active">{{ $rol->nombre }}</li>
        </ol>
    </nav>

    @if ($rol->nombre === \App\Modelo\Rol::ADMIN_SISTEMA)
        <div class="alert alert-warning small">
            <i class="bi bi-shield-exclamation me-1"></i>
            <strong>ADMIN_SISTEMA</strong> debe conservar todos los permisos del modulo
            <code>USUARIOS</code>. Sin ellos nadie podria volver a esta pantalla a corregir
            el error, y el sistema quedaria sin administracion posible.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('rol.update', $rol) }}">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-sliders me-1"></i> {{ $rol->nombre }}</span>
                        <span class="badge text-bg-light">
                            {{ count($asignados) }} permiso(s) asignado(s)
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 tabla-padron">
                            <thead class="table-light">
                                <tr>
                                    <th>Modulo</th>
                                    @foreach ($acciones as $accion)
                                        <th class="text-center">{{ $accion }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($matriz as $modulo => $porAccion)
                                <tr>
                                    <td class="fw-semibold">{{ $modulo }}</td>
                                    @foreach ($acciones as $accion)
                                        @php($permiso = $porAccion[$accion] ?? null)
                                        <td class="text-center">
                                            @if ($permiso === null)
                                                <span class="text-muted opacity-50" title="No existe en el catalogo">
                                                    <i class="bi bi-slash-circle"></i>
                                                </span>
                                            @else
                                                <input class="form-check-input" type="checkbox"
                                                       name="permisos[]" value="{{ $permiso->permiso_id }}"
                                                       id="p{{ $permiso->permiso_id }}"
                                                       title="{{ $permiso->descripcion }}"
                                                       @checked(in_array($permiso->permiso_id, $asignados, true))>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @error('permisos')
                        <div class="px-3 pt-3"><div class="alert alert-danger py-2 small mb-0">{{ $message }}</div></div>
                    @enderror

                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Los cambios se aplican de inmediato a todas las cuentas con este rol,
                            y quedan registrados en la auditoria (CA-HU12-04).
                        </small>
                        <div class="d-flex gap-2">
                            <a href="{{ route('rol.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Guardar permisos
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Sobre este rol</div>
                <div class="card-body small">
                    <p class="mb-2"><strong>{{ $rol->nombre }}</strong></p>
                    <p class="text-muted mb-0">{{ $rol->descripcion }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-1"></i> Cambios recientes
                </div>
                <div class="card-body py-3">
                    <div class="timeline">
                        @forelse ($auditoria as $log)
                            <div class="timeline-item">
                                <span class="timeline-punto bg-primary"></span>
                                <div class="small fw-semibold">{{ $log->accion }}</div>
                                <div class="small text-muted">{{ $log->detalle }}</div>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $log->fecha?->format('d/m/Y H:i') }} ·
                                    {{ $log->usuario?->username ?? 'sistema' }}
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Sin cambios registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
