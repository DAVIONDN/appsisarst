{{--
    Capa VISTA - Campos compartidos por el alta y la edicion de horarios.
    Los limites replican las restricciones de la base: la salida debe ser
    posterior a la entrada (ck_horario_rango) y la tolerancia va de 0 a 60
    minutos (ck_horario_tolerancia).
--}}
@php($h = $horario ?? null)

<div class="row g-3">

    <div class="col-12">
        <label for="nombre" class="form-label obligatorio">Nombre del horario</label>
        <input type="text" maxlength="80"
               class="form-control @error('nombre') is-invalid @enderror"
               id="nombre" name="nombre" value="{{ old('nombre', $h?->nombre) }}"
               placeholder="Ej.: Administrativo, Asistencial diurno" required>
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="hora_entrada" class="form-label obligatorio">Hora de entrada</label>
        <input type="time"
               class="form-control @error('hora_entrada') is-invalid @enderror"
               id="hora_entrada" name="hora_entrada"
               value="{{ old('hora_entrada', $h ? $h->horaCorta('hora_entrada') : '08:00') }}" required>
        @error('hora_entrada') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="hora_salida" class="form-label obligatorio">Hora de salida</label>
        <input type="time"
               class="form-control @error('hora_salida') is-invalid @enderror"
               id="hora_salida" name="hora_salida"
               value="{{ old('hora_salida', $h ? $h->horaCorta('hora_salida') : '16:00') }}" required>
        @error('hora_salida') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="tolerancia_min" class="form-label obligatorio">Tolerancia (minutos)</label>
        <input type="number" min="0" max="60"
               class="form-control @error('tolerancia_min') is-invalid @enderror"
               id="tolerancia_min" name="tolerancia_min"
               value="{{ old('tolerancia_min', $h?->tolerancia_min ?? 10) }}" required>
        <div class="form-text">Entre 0 y 60. Pasado ese margen se marca tardanza.</div>
        @error('tolerancia_min') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="dias_laborales" class="form-label obligatorio">Dias laborales</label>
        <select class="form-select @error('dias_laborales') is-invalid @enderror"
                id="dias_laborales" name="dias_laborales" required>
            @foreach ($dias as $clave => $etiqueta)
                <option value="{{ $clave }}"
                    @selected(old('dias_laborales', $h?->dias_laborales ?? 'LUN-VIE') === $clave)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Fuera de estos dias no se generan faltas.</div>
        @error('dias_laborales') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="alert alert-light border small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            La <strong>hora limite sin tardanza</strong> se calcula sumando la tolerancia a la
            hora de entrada. Con entrada 08:00 y tolerancia 10, quien marque 08:11 registra
            1 minuto de tardanza.
        </div>
    </div>
</div>
