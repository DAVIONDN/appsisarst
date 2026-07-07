{{--
    Capa VISTA - Campos compartidos por el alta y la edicion (HU-08).
    El establecimiento de destino solo se habilita cuando el tipo elegido
    lo exige (comision de servicio y rotacion), segun la bandera
    requiere_destino del catalogo.
--}}
@php($m = $movimiento ?? null)

<div class="row g-3">

    <div class="col-12 col-md-6">
        <label for="personal_id" class="form-label obligatorio">Trabajador</label>
        <select class="form-select @error('personal_id') is-invalid @enderror"
                id="personal_id" name="personal_id" required>
            <option value="">-- Seleccione --</option>
            @foreach ($personal as $p)
                <option value="{{ $p->personal_id }}"
                    @selected((string) old('personal_id', $m?->personal_id) === (string) $p->personal_id)>
                    {{ $p->nombre_completo }} — {{ $p->dni }} ({{ $p->area?->nombre }})
                </option>
            @endforeach
        </select>
        @error('personal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="tipo_movimiento_id" class="form-label obligatorio">Tipo de movimiento</label>
        <select class="form-select @error('tipo_movimiento_id') is-invalid @enderror"
                id="tipo_movimiento_id" name="tipo_movimiento_id" required
                data-destino="#bloque-destino">
            <option value="">-- Seleccione --</option>
            @foreach ($tipos as $t)
                <option value="{{ $t->tipo_movimiento_id }}"
                        data-requiere-destino="{{ $t->requiere_destino ? '1' : '0' }}"
                    @selected((string) old('tipo_movimiento_id', $m?->tipo_movimiento_id) === (string) $t->tipo_movimiento_id)>
                    {{ str_replace('_', ' ', $t->nombre) }}
                    @if ($t->descripcion) — {{ $t->descripcion }} @endif
                </option>
            @endforeach
        </select>
        @error('tipo_movimiento_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12" id="bloque-destino">
        <label for="establecimiento_destino_id" class="form-label">Establecimiento de destino</label>
        <select class="form-select @error('establecimiento_destino_id') is-invalid @enderror"
                id="establecimiento_destino_id" name="establecimiento_destino_id">
            <option value="">-- Sin destino --</option>
            @foreach ($establecimientos as $e)
                <option value="{{ $e->establecimiento_id }}"
                    @selected((string) old('establecimiento_destino_id', $m?->establecimiento_destino_id) === (string) $e->establecimiento_id)>
                    {{ $e->nombre }} — {{ $e->microrred }}
                </option>
            @endforeach
        </select>
        <div class="form-text">
            Obligatorio en comisiones de servicio y rotaciones; se ignora en los demas tipos.
        </div>
        @error('establecimiento_destino_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="fecha_inicio" class="form-label obligatorio">Fecha de inicio</label>
        <input type="date"
               class="form-control @error('fecha_inicio') is-invalid @enderror"
               id="fecha_inicio" name="fecha_inicio"
               value="{{ old('fecha_inicio', $m?->fecha_inicio?->format('Y-m-d')) }}" required>
        @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="fecha_fin" class="form-label obligatorio">Fecha de fin</label>
        <input type="date"
               class="form-control @error('fecha_fin') is-invalid @enderror"
               id="fecha_fin" name="fecha_fin"
               value="{{ old('fecha_fin', $m?->fecha_fin?->format('Y-m-d')) }}" required>
        <div class="form-text">No puede ser anterior a la de inicio.</div>
        @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="motivo" class="form-label obligatorio">Motivo</label>
        <textarea class="form-control @error('motivo') is-invalid @enderror"
                  id="motivo" name="motivo" rows="3" maxlength="255"
                  required>{{ old('motivo', $m?->motivo) }}</textarea>
        <div class="form-text">Minimo 10 caracteres. Es el sustento administrativo del movimiento.</div>
        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="alert alert-light border small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            El movimiento nace siempre en estado <strong>PENDIENTE</strong>. Desde alli solo
            puede pasar a APROBADO o RECHAZADO, y un aprobado se cierra como FINALIZADO
            cuando concluye. Un trabajador no puede tener dos movimientos vigentes que se
            crucen en el tiempo.
        </div>
    </div>
</div>
