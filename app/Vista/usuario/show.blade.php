{{--
    Capa VISTA - Ficha de la cuenta con su historial de auditoria.
    CA-HU11-03 y CA-HU12-04: la desactivacion conserva el historial y los
    cambios de rol quedan registrados.
--}}
@extends('layouts.app')

@section('titulo', 'Cuenta '.$usuario->username)
@section('subtitulo', $usuario->personal?->nombre_completo)

@section('contenido')

    <nav aria-label="ruta" class="mb-3 no-imprimir">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('usuario.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item active">{{ $usuario->username }}</li>
        </ol>
    </nav>

    <div class="row g-3">

        {{-- Ficha --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-person-circle display-3 text-primary opacity-75"></i>
                    <h2 class="h5 mt-2 mb-1 font-monospace">{{ $usuario->username }}</h2>
                    <p class="text-muted mb-2">{{ $usuario->personal?->nombre_completo }}</p>
                    <span class="badge badge-estado text-bg-{{ $usuario->color }}">{{ $usuario->estado }}</span>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item">
                        <span class="text-muted d-block">Correo institucional</span>
                        <span class="text-break">{{ $usuario->correo_institucional }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">DNI</span>
                        <span class="font-monospace">{{ $usuario->personal?->dni }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Area</span>
                        <span class="text-end">
                            {{ $usuario->personal?->area?->nombre }}
                            <div class="text-muted" style="font-size:.72rem">
                                {{ $usuario->personal?->area?->establecimiento?->nombre }}
                            </div>
                        </span>
                    </li>
                    <li class="list-group-item">
                        <span class="text-muted d-block mb-1">Roles</span>
                        @forelse ($usuario->roles as $rol)
                            <span class="badge text-bg-primary">{{ $rol->nombre }}</span>
                        @empty
                            <span class="badge text-bg-warning">SIN ROL ASIGNADO</span>
                        @endforelse
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Intentos fallidos</span>
                        <span class="{{ $usuario->intentos_fallidos > 0 ? 'text-danger fw-semibold' : '' }}">
                            {{ $usuario->intentos_fallidos }} de {{ \App\Modelo\Usuario::MAX_INTENTOS }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Ultimo acceso</span>
                        <span>{{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Enlace de recuperacion</span>
                        <span>
                            @if ($usuario->tieneTokenVigente())
                                <span class="badge text-bg-warning">
                                    vigente, {{ $usuario->minutosParaExpirar() }} min
                                </span>
                            @else
                                <span class="text-muted">ninguno</span>
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Creada</span>
                        <span>{{ $usuario->created_at?->format('d/m/Y H:i') }}</span>
                    </li>
                </ul>

                @if (auth()->user()->tienePermiso('USUARIOS', 'EDITAR'))
                    <div class="card-footer bg-white d-grid gap-2 no-imprimir">
                        <a href="{{ route('usuario.edit', $usuario) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Editar cuenta y roles
                        </a>
                        <a href="{{ route('usuario.clave.form', $usuario) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-key me-1"></i> Restablecer contrasena
                        </a>

                        @if ($usuario->estaBloqueado())
                            <form method="POST" action="{{ route('usuario.desbloquear', $usuario) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-warning w-100" type="submit">
                                    <i class="bi bi-unlock me-1"></i> Desbloquear cuenta
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Permisos efectivos + auditoría --}}
        <div class="col-lg-7">

            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-key me-1"></i> Permisos efectivos (CA-HU12-02)
                </div>
                <div class="card-body">
                    @php($permisos = $usuario->roles->pluck('permisos')->flatten()->unique('permiso_id')->groupBy('modulo'))
                    @if ($permisos->isEmpty())
                        <p class="text-muted small mb-0">
                            Esta cuenta no tiene permisos: puede iniciar sesion pero no accedera
                            a ningun modulo.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 tabla-padron">
                                <thead class="table-light">
                                    <tr><th>Modulo</th><th>Acciones permitidas</th></tr>
                                </thead>
                                <tbody>
                                @foreach ($permisos as $modulo => $lista)
                                    <tr>
                                        <td class="fw-semibold">{{ $modulo }}</td>
                                        <td>
                                            @foreach ($lista->sortBy('accion') as $p)
                                                <span class="badge text-bg-success">{{ $p->accion }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-1"></i> Historial de la cuenta</span>
                    <span class="badge text-bg-light">{{ $auditoria->count() }}</span>
                </div>
                <div class="card-body py-3" style="max-height:420px; overflow-y:auto">
                    <div class="timeline">
                        @forelse ($auditoria as $log)
                            @php($critico = in_array($log->accion, ['BLOQUEAR_CUENTA', 'ACCESO_FALLIDO', 'ACCESO_BLOQUEADO'], true))
                            <div class="timeline-item">
                                <span class="timeline-punto bg-{{ $critico ? 'danger' : 'primary' }}"></span>
                                <div class="small fw-semibold">{{ $log->accion }}</div>
                                <div class="small text-muted">{{ $log->detalle }}</div>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $log->fecha?->format('d/m/Y H:i') }} ·
                                    {{ $log->usuario?->username ?? 'sistema' }}
                                    @if ($log->ip_origen) · {{ $log->ip_origen }} @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Sin actividad registrada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
