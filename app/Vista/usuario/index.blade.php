{{--
    Capa VISTA - HU-11: Gestion de usuarios del sistema.
    Figuras 4.14 y 4.15 del documento de diseno.
--}}
@extends('layouts.app')

@section('titulo', 'Usuarios del Sistema')
@section('subtitulo', 'HU-11 · Cuentas de acceso')

@section('contenido')

    <div class="row g-3 mb-3">
        @foreach ([
            ['Activas',    $resumen['activos'],    'success', 'bi-person-check-fill'],
            ['Inactivas',  $resumen['inactivos'],  'info',    'bi-person-dash'],
            ['Bloqueadas', $resumen['bloqueados'], 'danger',  'bi-lock-fill'],
            ['Sin rol',    $resumen['sin_rol'],    'warning', 'bi-exclamation-triangle-fill'],
        ] as [$titulo, $valor, $color, $icono])
            <div class="col-6 col-lg-3">
                <div class="card kpi-card kpi-{{ $color }}">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="kpi-titulo">{{ $titulo }}</div>
                            <div class="kpi-valor">{{ $valor }}</div>
                        </div>
                        <i class="bi {{ $icono }} fs-3 text-{{ $color }} opacity-50"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($sinCuenta > 0)
        <div class="alert alert-light border no-imprimir">
            <i class="bi bi-info-circle me-1"></i>
            Hay <strong>{{ $sinCuenta }}</strong> trabajador(es) activo(s) del padron sin cuenta de acceso.
        </div>
    @endif

    {{-- Filtros --}}
    <div class="card mb-3 no-imprimir">
        <div class="card-body">
            <form method="GET" action="{{ route('usuario.index') }}" class="row g-2 align-items-end">

                <div class="col-12 col-md-4">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="search" class="form-control" id="buscar" name="buscar"
                           value="{{ $filtros['buscar'] }}" placeholder="Usuario, correo, DNI o nombre">
                </div>

                <div class="col-6 col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected($filtros['estado'] === $estado)>
                                {{ ucfirst(strtolower($estado)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="rol_id" class="form-label">Rol</label>
                    <select class="form-select" id="rol_id" name="rol_id">
                        <option value="">Todos</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->rol_id }}"
                                @selected((string) $filtros['rol_id'] === (string) $rol->rol_id)>
                                {{ $rol->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Aplicar filtros
                    </button>
                    <a href="{{ route('usuario.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </a>
                    @if (auth()->user()->tienePermiso('USUARIOS', 'ESCRIBIR'))
                        <a href="{{ route('usuario.create') }}" class="btn btn-success ms-auto">
                            <i class="bi bi-person-plus me-1"></i> Nueva cuenta
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Listado --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Cuentas registradas</span>
            <span class="badge text-bg-light">{{ $usuarios->total() }} cuenta(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th>Usuario</th>
                        <th>Trabajador</th>
                        <th>Correo institucional</th>
                        <th>Roles</th>
                        <th>Estado</th>
                        <th>Ultimo acceso</th>
                        <th class="text-end no-imprimir">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($usuarios as $u)
                    <tr>
                        <td>
                            <a href="{{ route('usuario.show', $u) }}"
                               class="text-decoration-none fw-semibold font-monospace">
                                {{ $u->username }}
                            </a>
                            @if ($u->intentos_fallidos > 0 && ! $u->estaBloqueado())
                                <div class="text-warning" style="font-size:.72rem">
                                    {{ $u->intentos_fallidos }} intento(s) fallido(s)
                                </div>
                            @endif
                        </td>
                        <td class="small">
                            {{ $u->personal?->nombre_completo }}
                            <div class="text-muted" style="font-size:.72rem">
                                {{ $u->personal?->area?->nombre }}
                            </div>
                        </td>
                        <td class="small text-break">{{ $u->correo_institucional }}</td>
                        <td>
                            @forelse ($u->roles as $rol)
                                <span class="badge text-bg-primary">{{ $rol->nombre }}</span>
                            @empty
                                <span class="badge text-bg-warning">SIN ROL</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="badge badge-estado text-bg-{{ $u->color }}">{{ $u->estado }}</span>
                        </td>
                        <td class="small">
                            {{ $u->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}
                        </td>
                        <td class="text-end no-imprimir text-nowrap">
                            <a href="{{ route('usuario.show', $u) }}"
                               class="btn btn-sm btn-outline-primary" title="Ver ficha">
                                <i class="bi bi-eye"></i>
                            </a>

                            @if (auth()->user()->tienePermiso('USUARIOS', 'EDITAR'))
                                <a href="{{ route('usuario.edit', $u) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                @if ($u->estaBloqueado())
                                    <button type="button" class="btn btn-sm btn-outline-warning js-confirmar"
                                            title="Desbloquear"
                                            data-form="#form-desbloq-{{ $u->usuario_id }}"
                                            data-titulo="¿Desbloquear la cuenta?"
                                            data-texto="{{ $u->username }} — se reiniciara el contador de intentos.">
                                        <i class="bi bi-unlock"></i>
                                    </button>
                                    <form id="form-desbloq-{{ $u->usuario_id }}" class="d-none"
                                          method="POST" action="{{ route('usuario.desbloquear', $u) }}">
                                        @csrf @method('PATCH')
                                    </form>
                                @elseif ($u->estaActivo())
                                    <button type="button" class="btn btn-sm btn-outline-danger js-confirmar"
                                            title="Desactivar"
                                            data-form="#form-baja-{{ $u->usuario_id }}"
                                            data-titulo="¿Desactivar la cuenta?"
                                            data-texto="{{ $u->username }} perdera el acceso. Su historial se conserva.">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                    <form id="form-baja-{{ $u->usuario_id }}" class="d-none"
                                          method="POST" action="{{ route('usuario.desactivar', $u) }}">
                                        @csrf @method('PATCH')
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-success js-confirmar"
                                            title="Reactivar"
                                            data-form="#form-alta-{{ $u->usuario_id }}"
                                            data-titulo="¿Reactivar la cuenta?"
                                            data-texto="{{ $u->username }}">
                                        <i class="bi bi-person-check"></i>
                                    </button>
                                    <form id="form-alta-{{ $u->usuario_id }}" class="d-none"
                                          method="POST" action="{{ route('usuario.reactivar', $u) }}">
                                        @csrf @method('PATCH')
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No se encontraron cuentas con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="card-footer bg-white no-imprimir">{{ $usuarios->links() }}</div>
        @endif
    </div>

@endsection
