{{--
    Capa VISTA - HU-09 detalle + HU-10 cambio de estado.
    Figuras 3.11 y 3.12 del documento de diseno.

    Los botones de accion se generan a partir de las transiciones que la
    maquina de estados admite desde el estado actual: si un estado no es
    alcanzable, su boton no existe. Asi la interfaz no puede proponer una
    transicion que el dominio va a rechazar.
--}}
@extends('layouts.app')

@section('titulo', 'Movimiento #'.$movimiento->movimiento_id)
@section('subtitulo', $movimiento->personal?->nombre_completo.' · '.str_replace('_', ' ', $movimiento->tipo?->nombre ?? ''))

@section('contenido')

    <nav aria-label="ruta" class="mb-3 no-imprimir">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('movimiento.index') }}">Movimientos</a></li>
            <li class="breadcrumb-item active">#{{ $movimiento->movimiento_id }}</li>
        </ol>
    </nav>

    <div class="row g-3">

        {{-- ------------------------------------------------------- --}}
        {{--  Ficha del movimiento                                   --}}
        {{-- ------------------------------------------------------- --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Detalle del movimiento</span>
                    <span class="badge badge-estado text-bg-{{ $movimiento->color }}">
                        <i class="bi {{ $movimiento->icono }}"></i> {{ $movimiento->estado }}
                    </span>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Trabajador</span>
                        <span class="text-end fw-semibold">{{ $movimiento->personal?->nombre_completo }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">DNI</span>
                        <span class="font-monospace">{{ $movimiento->personal?->dni }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Area de origen</span>
                        <span class="text-end">
                            {{ $movimiento->personal?->area?->nombre }}
                            <div class="text-muted" style="font-size:.72rem">
                                {{ $movimiento->personal?->area?->establecimiento?->nombre }}
                            </div>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Tipo</span>
                        <span>{{ str_replace('_', ' ', $movimiento->tipo?->nombre ?? '') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Establecimiento de destino</span>
                        <span class="text-end">{{ $movimiento->establecimientoDestino?->nombre ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Periodo</span>
                        <span>{{ $movimiento->periodo }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Duracion</span>
                        <span>{{ $movimiento->dias }} dia(s)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Situacion</span>
                        <span class="badge text-bg-light">{{ $movimiento->situacion }}</span>
                    </li>
                    <li class="list-group-item">
                        <span class="text-muted d-block mb-1">Motivo</span>
                        {{ $movimiento->motivo }}
                    </li>
                    @if ($movimiento->motivo_rechazo)
                        <li class="list-group-item bg-danger-subtle">
                            <span class="text-muted d-block mb-1">Motivo del rechazo</span>
                            {{ $movimiento->motivo_rechazo }}
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Registrado</span>
                        <span>{{ $movimiento->created_at?->format('d/m/Y H:i') }}</span>
                    </li>
                </ul>

                @if ($movimiento->es_editable && auth()->user()->tienePermiso('MOVIMIENTOS', 'ESCRIBIR'))
                    <div class="card-footer bg-white no-imprimir">
                        <a href="{{ route('movimiento.edit', $movimiento) }}"
                           class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-pencil me-1"></i> Editar movimiento
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- ------------------------------------------------------- --}}
        {{--  HU-10: cambio de estado                                --}}
        {{-- ------------------------------------------------------- --}}
        <div class="col-lg-7">

            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-diagram-2 me-1"></i> Ciclo de vida del movimiento
                </div>
                <div class="card-body">

                    {{-- Máquina de estados visual --}}
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        @foreach (['PENDIENTE', 'APROBADO', 'FINALIZADO'] as $i => $paso)
                            @php($alcanzado = match ($paso) {
                                'PENDIENTE'  => true,
                                'APROBADO'   => in_array($movimiento->estado, ['APROBADO', 'FINALIZADO'], true),
                                'FINALIZADO' => $movimiento->estado === 'FINALIZADO',
                            })
                            @if ($i > 0)
                                <i class="bi bi-arrow-right text-muted"></i>
                            @endif
                            <span class="badge {{ $alcanzado ? 'text-bg-primary' : 'text-bg-light text-muted' }}">
                                {{ $paso }}
                            </span>
                        @endforeach

                        @if ($movimiento->estado === 'RECHAZADO')
                            <i class="bi bi-arrow-return-right text-muted"></i>
                            <span class="badge text-bg-danger">RECHAZADO</span>
                        @endif
                    </div>

                    @if ($movimiento->es_terminal)
                        <div class="alert alert-secondary small mb-0">
                            <i class="bi bi-lock-fill me-1"></i>
                            Este movimiento esta <strong>{{ $movimiento->estado }}</strong>: cerro su ciclo
                            y no admite mas cambios de estado.
                        </div>
                    @elseif (! auth()->user()->tienePermiso('MOVIMIENTOS', 'EDITAR'))
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Su rol no tiene permiso para cambiar el estado de los movimientos
                            (se requiere <code>MOVIMIENTOS.EDITAR</code>).
                        </div>
                    @else
                        <div class="d-flex flex-wrap gap-2 no-imprimir">
                            @foreach ($movimiento->transicionesPosibles() as $destino)
                                <button type="button"
                                        class="btn btn-{{ \App\Modelo\MovimientoInstitucional::COLORES[$destino] }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-{{ strtolower($destino) }}">
                                    <i class="bi {{ \App\Modelo\MovimientoInstitucional::ICONOS[$destino] }} me-1"></i>
                                    {{ ucfirst(strtolower($destino)) }}
                                </button>
                            @endforeach
                        </div>

                        @if ($movimiento->es_finalizable)
                            <p class="small text-muted mt-2 mb-0">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                El periodo de este movimiento ya termino: corresponde finalizarlo.
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Historial de cambios (CA-HU09-02 y CA-HU10-02) --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-1"></i> Historial de cambios</span>
                    <span class="badge text-bg-light">{{ $auditoria->count() }}</span>
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
                                    {{ $log->usuario?->personal?->nombre_completo ?? $log->usuario?->username ?? 'sistema' }}
                                    @if ($log->ip_origen) · {{ $log->ip_origen }} @endif
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

    {{-- ------------------------------------------------------------- --}}
    {{--  Modales de transición                                        --}}
    {{-- ------------------------------------------------------------- --}}
    @if (! $movimiento->es_terminal && auth()->user()->tienePermiso('MOVIMIENTOS', 'EDITAR'))
        @foreach ($movimiento->transicionesPosibles() as $destino)
            <div class="modal fade" id="modal-{{ strtolower($destino) }}" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('movimiento.estado', $movimiento) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="{{ $destino }}">

                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title h6">
                                    {{ ucfirst(strtolower($destino)) }} el movimiento
                                    #{{ $movimiento->movimiento_id }}
                                </h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <p class="small mb-3">
                                    <strong>{{ $movimiento->personal?->nombre_completo }}</strong><br>
                                    {{ str_replace('_', ' ', $movimiento->tipo?->nombre ?? '') }} ·
                                    {{ $movimiento->periodo }}
                                </p>

                                @if ($destino === 'RECHAZADO')
                                    <label for="motivo_rechazo" class="form-label obligatorio">
                                        Motivo del rechazo
                                    </label>
                                    <textarea class="form-control" id="motivo_rechazo" name="motivo_rechazo"
                                              rows="3" maxlength="255" required minlength="10"
                                              placeholder="Explique por que se rechaza la solicitud"></textarea>
                                    <div class="form-text">
                                        Obligatorio: queda registrado en el movimiento y en la auditoria.
                                    </div>
                                @endif

                                @if ($destino === 'APROBADO' && $areasDestino !== null)
                                    <div class="alert alert-warning small">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        Al aprobar esta <strong>rotacion</strong>, el trabajador quedara
                                        trasladado de forma permanente al area que elija. El padron se
                                        actualiza automaticamente.
                                    </div>

                                    <label for="area_destino_id" class="form-label obligatorio">
                                        Area de destino en {{ $movimiento->establecimientoDestino?->nombre }}
                                    </label>
                                    <select class="form-select" id="area_destino_id" name="area_destino_id" required>
                                        <option value="">-- Seleccione --</option>
                                        @foreach ($areasDestino as $area)
                                            <option value="{{ $area->area_id }}">{{ $area->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @if ($areasDestino->isEmpty())
                                        <div class="form-text text-danger">
                                            El establecimiento de destino no tiene areas activas.
                                            Registre una antes de aprobar la rotacion.
                                        </div>
                                    @endif
                                @endif

                                @if ($destino === 'FINALIZADO')
                                    <p class="small text-muted mb-0">
                                        Se cerrara el movimiento por haber concluido su periodo.
                                        Es la ultima transicion: no admite reversa.
                                    </p>
                                @endif
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="btn btn-{{ \App\Modelo\MovimientoInstitucional::COLORES[$destino] }}">
                                    Confirmar {{ strtolower($destino) }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif

@endsection
