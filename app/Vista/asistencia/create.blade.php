{{--
    Capa VISTA - HU-05: Marcacion de entrada y salida.
    Figuras 2.8 y 2.9 del documento de diseno.
    El sello de tiempo se propone con la hora del servidor (CA-HU05-01) y
    el operador puede ajustarlo si registra una marcacion atrasada.
--}}
@extends('layouts.app')

@section('titulo', 'Marcacion de asistencia')
@section('subtitulo', 'HU-05 · Registro de entradas y salidas')

@section('contenido')

    @if ($sinHorario > 0)
        <div class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ $sinHorario }} trabajador(es) sin horario asignado. Sus marcaciones se
            guardaran como PUNTUAL porque el sistema no tiene contra que compararlas
            (CA-HU16-03).
        </div>
    @endif

    <div class="row g-3">

        {{-- Formulario de marcacion --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock me-1"></i> Registrar marcacion
                </div>
                <form method="POST" action="{{ route('asistencia.store') }}" novalidate>
                    @csrf
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="personal_id" class="form-label obligatorio">Trabajador</label>
                            <select class="form-select @error('personal_id') is-invalid @enderror"
                                    id="personal_id" name="personal_id" required>
                                <option value="">-- Seleccione --</option>
                                @foreach ($personal as $p)
                                    <option value="{{ $p->personal_id }}"
                                        @selected((string) old('personal_id') === (string) $p->personal_id)>
                                        {{ $p->nombre_completo }} — {{ $p->dni }}
                                        @if ($p->horario)
                                            ({{ $p->horario->nombre }}, limite {{ $p->horario->hora_limite }})
                                        @else
                                            (SIN HORARIO)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('personal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label obligatorio d-block">Tipo de marcacion</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tipo" id="tipo-entrada"
                                       value="ENTRADA" @checked(old('tipo', 'ENTRADA') === 'ENTRADA') required>
                                <label class="btn btn-outline-success" for="tipo-entrada">
                                    <i class="bi bi-box-arrow-in-right"></i> Entrada
                                </label>

                                <input type="radio" class="btn-check" name="tipo" id="tipo-salida"
                                       value="SALIDA" @checked(old('tipo') === 'SALIDA')>
                                <label class="btn btn-outline-primary" for="tipo-salida">
                                    <i class="bi bi-box-arrow-right"></i> Salida
                                </label>
                            </div>
                            @error('tipo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label for="fecha" class="form-label obligatorio">Fecha</label>
                                <input type="text" class="form-control js-fecha @error('fecha') is-invalid @enderror"
                                       id="fecha" name="fecha" value="{{ old('fecha', $fecha) }}" required>
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-5">
                                <label for="hora" class="form-label obligatorio">Hora</label>
                                <input type="time" class="form-control @error('hora') is-invalid @enderror"
                                       id="hora" name="hora" value="{{ old('hora', now()->format('H:i')) }}" required>
                                @error('hora') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="observacion" class="form-label">Observacion</label>
                            <textarea class="form-control @error('observacion') is-invalid @enderror"
                                      id="observacion" name="observacion" rows="2"
                                      maxlength="255">{{ old('observacion') }}</textarea>
                            <div class="form-text">Opcional. Solo se guarda en la marcacion de entrada.</div>
                            @error('observacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="card-footer bg-white d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Registrar marcacion
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Jornadas ya registradas ese dia --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Jornadas del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                    <span class="badge text-bg-light">{{ $jornadasDelDia->count() }} registro(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 tabla-padron">
                        <thead class="table-light">
                            <tr>
                                <th>Trabajador</th><th>Entrada</th><th>Salida</th>
                                <th>Estado</th><th>Tardanza</th><th class="text-end">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($jornadasDelDia as $a)
                            <tr>
                                <td class="small">
                                    {{ $a->personal?->nombre_completo }}
                                    <div class="text-muted font-monospace" style="font-size:.72rem">
                                        {{ $a->personal?->dni }}
                                    </div>
                                </td>
                                <td class="font-monospace">{{ $a->entrada_corta }}</td>
                                <td class="font-monospace">
                                    {{ $a->salida_corta }}
                                    @if ($a->jornada_abierta)
                                        <span class="badge text-bg-light">abierta</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $a->color }}">
                                        <i class="bi {{ $a->icono }}"></i> {{ $a->estado }}
                                    </span>
                                </td>
                                <td class="small">
                                    {{ $a->minutos_tardanza > 0 ? $a->minutos_tardanza.' min' : '—' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('asistencia.edit', $a) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Corregir">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Aun no hay marcaciones registradas para esta fecha.
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
