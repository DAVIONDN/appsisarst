{{--
    Capa VISTA - Campos compartidos por HU-01 (alta) y HU-02 (edicion).
    Los campos obligatorios son los que fija la historia HU-01
    (CA-HU01-01 y CA-HU01-02).
--}}
@php($p = $personal ?? null)

<div class="row g-3">

    {{-- Datos de identificacion --}}
    <div class="col-12">
        <h2 class="h6 text-primary border-bottom pb-2 mb-0">
            <i class="bi bi-person-vcard me-1"></i> Datos personales
        </h2>
    </div>

    <div class="col-12 col-md-3">
        <label for="dni" class="form-label obligatorio">DNI</label>
        <input type="text" inputmode="numeric" maxlength="8"
               class="form-control font-monospace @error('dni') is-invalid @enderror"
               id="dni" name="dni" value="{{ old('dni', $p?->dni) }}" required>
        @error('dni') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="nombres" class="form-label obligatorio">Nombres</label>
        <input type="text" maxlength="100"
               class="form-control @error('nombres') is-invalid @enderror"
               id="nombres" name="nombres" value="{{ old('nombres', $p?->nombres) }}" required>
        @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-5">
        <label for="apellidos" class="form-label obligatorio">Apellidos</label>
        <input type="text" maxlength="100"
               class="form-control @error('apellidos') is-invalid @enderror"
               id="apellidos" name="apellidos" value="{{ old('apellidos', $p?->apellidos) }}" required>
        @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="telefono" class="form-label obligatorio">Telefono</label>
        <input type="text" maxlength="15"
               class="form-control @error('telefono') is-invalid @enderror"
               id="telefono" name="telefono" value="{{ old('telefono', $p?->telefono) }}" required>
        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="correo" class="form-label obligatorio">Correo electronico</label>
        <input type="email" maxlength="120"
               class="form-control @error('correo') is-invalid @enderror"
               id="correo" name="correo" value="{{ old('correo', $p?->correo) }}" required>
        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Datos laborales --}}
    <div class="col-12 mt-4">
        <h2 class="h6 text-primary border-bottom pb-2 mb-0">
            <i class="bi bi-briefcase me-1"></i> Datos laborales
        </h2>
    </div>

    <div class="col-12 col-md-6">
        <label for="area_id" class="form-label obligatorio">Area de trabajo</label>
        <select class="form-select @error('area_id') is-invalid @enderror"
                id="area_id" name="area_id" required>
            <option value="">-- Seleccione --</option>
            @foreach ($areas as $area)
                <option value="{{ $area->area_id }}"
                    @selected((string) old('area_id', $p?->area_id) === (string) $area->area_id)>
                    {{ $area->nombre }} — {{ $area->establecimiento?->nombre }}
                </option>
            @endforeach
        </select>
        @error('area_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="cargo" class="form-label obligatorio">Cargo</label>
        <input type="text" maxlength="80"
               class="form-control @error('cargo') is-invalid @enderror"
               id="cargo" name="cargo" value="{{ old('cargo', $p?->cargo) }}" required>
        @error('cargo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="condicion_laboral" class="form-label obligatorio">Condicion laboral</label>
        <select class="form-select @error('condicion_laboral') is-invalid @enderror"
                id="condicion_laboral" name="condicion_laboral" required>
            <option value="">-- Seleccione --</option>
            @foreach ($condiciones as $condicion)
                <option value="{{ $condicion }}"
                    @selected(old('condicion_laboral', $p?->condicion_laboral) === $condicion)>
                    {{ $condicion }}
                </option>
            @endforeach
        </select>
        @error('condicion_laboral') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="fecha_ingreso" class="form-label obligatorio">Fecha de ingreso</label>
        <input type="text"
               class="form-control js-fecha @error('fecha_ingreso') is-invalid @enderror"
               id="fecha_ingreso" name="fecha_ingreso"
               value="{{ old('fecha_ingreso', $p?->fecha_ingreso?->format('Y-m-d')) }}"
               placeholder="AAAA-MM-DD" required>
        <div class="form-text">No puede ser una fecha futura.</div>
        @error('fecha_ingreso') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="horario_id" class="form-label">Horario asignado</label>
        <select class="form-select @error('horario_id') is-invalid @enderror"
                id="horario_id" name="horario_id">
            <option value="">Sin horario asignado</option>
            @foreach ($horarios as $horario)
                <option value="{{ $horario->horario_id }}"
                    @selected((string) old('horario_id', $p?->horario_id) === (string) $horario->horario_id)>
                    {{ $horario->etiqueta }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Opcional. Sin horario no se evaluan tardanzas (Sprint 2).</div>
        @error('horario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
