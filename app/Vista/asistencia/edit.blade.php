{{--
    Capa VISTA - Correccion manual de una jornada.
    La coherencia entre estado y horas la exige la base de datos con la
    restriccion ck_asistencia_coherencia; aqui se explica al operador para
    que entienda por que ciertas combinaciones no se admiten.
--}}
@extends('layouts.app')

@section('titulo', 'Corregir jornada')
@section('subtitulo', $asistencia->personal?->nombre_completo.' · '.$asistencia->fecha?->format('d/m/Y'))

@section('contenido')

    <nav aria-label="ruta" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Corregir jornada</li>
        </ol>
    </nav>

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('asistencia.update', $asistencia) }}" novalidate>
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-pencil-square me-1"></i> Datos de la jornada</span>
                        <span class="badge text-bg-{{ $asistencia->color }}">{{ $asistencia->estado }}</span>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="estado" class="form-label obligatorio">Estado</label>
                            <select class="form-select @error('estado') is-invalid @enderror"
                                    id="estado" name="estado" required>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado }}"
                                        @selected(old('estado', $asistencia->estado) === $estado)>
                                        {{ $estado }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="hora_entrada" class="form-label">Hora de entrada</label>
                                <input type="time"
                                       class="form-control @error('hora_entrada') is-invalid @enderror"
                                       id="hora_entrada" name="hora_entrada"
                                       value="{{ old('hora_entrada', $asistencia->hora_entrada ? substr((string) $asistencia->hora_entrada, 0, 5) : '') }}">
                                @error('hora_entrada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label for="hora_salida" class="form-label">Hora de salida</label>
                                <input type="time"
                                       class="form-control @error('hora_salida') is-invalid @enderror"
                                       id="hora_salida" name="hora_salida"
                                       value="{{ old('hora_salida', $asistencia->hora_salida ? substr((string) $asistencia->hora_salida, 0, 5) : '') }}">
                                @error('hora_salida') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="observacion" class="form-label">Observacion</label>
                            <textarea class="form-control @error('observacion') is-invalid @enderror"
                                      id="observacion" name="observacion" rows="2"
                                      maxlength="255">{{ old('observacion', $asistencia->observacion) }}</textarea>
                            @error('observacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-end gap-2">
                        <a href="{{ route('asistencia.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar correccion
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Reglas que aplica el sistema</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item">
                        <span class="badge text-bg-success">PUNTUAL</span>
                        exige hora de entrada y no admite minutos de tardanza.
                    </li>
                    <li class="list-group-item">
                        <span class="badge text-bg-warning">TARDANZA</span>
                        exige hora de entrada posterior a la hora limite del horario asignado.
                        Los minutos los calcula el sistema, no se escriben a mano.
                    </li>
                    <li class="list-group-item">
                        <span class="badge text-bg-danger">FALTA</span> y
                        <span class="badge text-bg-info">JUSTIFICADO</span>
                        no admiten horas: el trabajador no se presento.
                    </li>
                </ul>
                <div class="card-footer bg-white small text-muted">
                    Toda correccion queda registrada en el log de auditoria con su
                    autor y su fecha (RNF-10).
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Horario del trabajador</div>
                <div class="card-body small">
                    @if ($asistencia->personal?->horario)
                        @php($h = $asistencia->personal->horario)
                        <p class="mb-1"><strong>{{ $h->nombre }}</strong></p>
                        <p class="mb-1">Entrada {{ $h->horaCorta('hora_entrada') }} ·
                           Salida {{ $h->horaCorta('hora_salida') }}</p>
                        <p class="mb-1">Tolerancia: {{ $h->tolerancia_min }} minutos</p>
                        <p class="mb-0">Hora limite sin tardanza:
                           <strong class="font-monospace">{{ $h->hora_limite }}</strong></p>
                    @else
                        <p class="text-muted mb-0">
                            El trabajador no tiene horario asignado, por lo que no puede
                            marcarse como TARDANZA (CA-HU16-03).
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
