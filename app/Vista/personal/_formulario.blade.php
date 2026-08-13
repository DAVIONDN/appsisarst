{{--
    Capa VISTA - Campos compartidos por HU-01 (alta) y HU-02 (edicion).
    Los campos obligatorios son los que fija la historia HU-01
    (CA-HU01-01 y CA-HU01-02).

    Validaciones implementadas:
      - Nombres / Apellidos : solo letras, tildes, ñ, espacios y guiones
      - Telefono            : solo digitos, +, - y espacios
      - DNI                 : solo 8 digitos numericos
      - Correo              : formato email RFC (type="email" + backend)
      - Fecha ingreso       : no puede ser futura
--}}
@php($p = $personal ?? null)

<div class="row g-3">

    {{-- Datos de identificacion --}}
    <div class="col-12">
        <h2 class="h6 text-primary border-bottom pb-2 mb-0">
            <i class="bi bi-person-vcard me-1"></i> Datos personales
        </h2>
    </div>

    {{-- DNI: solo 8 digitos --}}
    <div class="col-12 col-md-3">
        <label for="dni" class="form-label obligatorio">DNI</label>
        <input type="text" inputmode="numeric" maxlength="8"
               pattern="\d{8}"
               title="El DNI debe tener exactamente 8 digitos numericos."
               class="form-control font-monospace @error('dni') is-invalid @enderror"
               id="dni" name="dni" value="{{ old('dni', $p?->dni) }}"
               autocomplete="off" required>
        @error('dni')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">8 digitos numericos.</div>
        @enderror
    </div>

    {{-- Nombres: solo letras, tildes, ñ, espacios y guiones --}}
    <div class="col-12 col-md-4">
        <label for="nombres" class="form-label obligatorio">Nombres</label>
        <input type="text" maxlength="100"
               pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀàÈèÌìÒòÙù\s\-\.]+"
               title="Solo letras, espacios y guiones. No se permiten numeros."
               class="form-control js-solo-letras @error('nombres') is-invalid @enderror"
               id="nombres" name="nombres" value="{{ old('nombres', $p?->nombres) }}"
               autocomplete="off" required>
        @error('nombres')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Solo letras y espacios.</div>
        @enderror
    </div>

    {{-- Apellidos: solo letras, tildes, ñ, espacios y guiones --}}
    <div class="col-12 col-md-5">
        <label for="apellidos" class="form-label obligatorio">Apellidos</label>
        <input type="text" maxlength="100"
               pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀàÈèÌìÒòÙù\s\-\.]+"
               title="Solo letras, espacios y guiones. No se permiten numeros."
               class="form-control js-solo-letras @error('apellidos') is-invalid @enderror"
               id="apellidos" name="apellidos" value="{{ old('apellidos', $p?->apellidos) }}"
               autocomplete="off" required>
        @error('apellidos')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Solo letras y espacios.</div>
        @enderror
    </div>

    {{-- Telefono: solo digitos, +, - y espacios --}}
    <div class="col-12 col-md-6">
        <label for="telefono" class="form-label obligatorio">Telefono</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="tel" inputmode="numeric" maxlength="15"
                   pattern="[0-9+\- ]{6,15}"
                   title="Solo digitos (0-9). Ejemplo: 987654321"
                   class="form-control js-solo-telefono @error('telefono') is-invalid @enderror"
                   id="telefono" name="telefono" value="{{ old('telefono', $p?->telefono) }}"
                   placeholder="Ej: 987654321" autocomplete="tel" required>
        </div>
        @error('telefono')
            <div class="text-danger small mt-1">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
        @else
            <div class="form-text">Solo numeros (6 a 15 digitos). Ejemplo: 987654321</div>
        @enderror
    </div>

    {{-- Correo electronico --}}
    <div class="col-12 col-md-6">
        <label for="correo" class="form-label obligatorio">Correo electronico</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" maxlength="120"
                   title="Ingrese un correo electronico valido. Ejemplo: nombre@dominio.com"
                   class="form-control @error('correo') is-invalid @enderror"
                   id="correo" name="correo" value="{{ old('correo', $p?->correo) }}"
                   placeholder="nombre@dominio.com" autocomplete="email" required>
        </div>
        @error('correo')
            <div class="text-danger small mt-1">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
        @else
            <div class="form-text">Ejemplo: juan.perez@redsaludtayacaja.gob.pe</div>
        @enderror
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

{{-- ─── Validacion en tiempo real ─────────────────────────────────────────── --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Helpers ──────────────────────────────────────────────────────── */

    /** Marca el campo como invalido y muestra/oculta el mensaje de ayuda. */
    function marcarError(input, mensaje) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        var fb = input.parentElement.querySelector('.fb-dinamico');
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback fb-dinamico';
            input.parentElement.appendChild(fb);
        }
        fb.textContent = mensaje;
    }

    function marcarOk(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        var fb = input.parentElement.querySelector('.fb-dinamico');
        if (fb) fb.textContent = '';
    }

    function limpiarEstado(input) {
        input.classList.remove('is-invalid', 'is-valid');
        var fb = input.parentElement.querySelector('.fb-dinamico');
        if (fb) fb.textContent = '';
    }

    /* ── Regex de validacion ─────────────────────────────────────────── */
    // Letras Unicode (incluye tildes y ñ), espacios, guiones y puntos
    var reLetras  = /^[À-ža-zA-Z\s\-\.\']+$/;
    // Telefono: digitos, +, - y espacios
    var reTel     = /^[0-9+\- ]{6,15}$/;
    // Caracteres prohibidos en campos de texto de nombre
    var reDigitos = /\d/;

    /* ── Campos de solo letras (Nombres / Apellidos) ─────────────────── */
    document.querySelectorAll('.js-solo-letras').forEach(function (input) {

        /* Bloquear numeros mientras escribe */
        input.addEventListener('keypress', function (e) {
            if (/\d/.test(e.key)) {
                e.preventDefault();
                parpadear(input);
            }
        });

        /* Limpiar al pegar: quitar digitos del texto pegado */
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            var texto = (e.clipboardData || window.clipboardData).getData('text');
            var limpio = texto.replace(/\d/g, '');
            document.execCommand('insertText', false, limpio);
        });

        /* Validar al salir del campo */
        input.addEventListener('blur', function () {
            var val = input.value.trim();
            if (val === '') { limpiarEstado(input); return; }
            if (reDigitos.test(val)) {
                marcarError(input, 'Este campo no acepta numeros. Use solo letras y espacios.');
            } else if (!reLetras.test(val)) {
                marcarError(input, 'Solo se permiten letras, espacios y guiones.');
            } else {
                marcarOk(input);
            }
        });

        input.addEventListener('input', function () {
            if (!reDigitos.test(input.value)) limpiarEstado(input);
        });
    });

    /* ── Campo de telefono ───────────────────────────────────────────── */
    document.querySelectorAll('.js-solo-telefono').forEach(function (input) {

        /* Bloquear letras mientras escribe (permitir numeros, +, -, espacio) */
        input.addEventListener('keypress', function (e) {
            if (!/[0-9+\- ]/.test(e.key)) {
                e.preventDefault();
                parpadear(input);
            }
        });

        /* Limpiar al pegar */
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            var texto = (e.clipboardData || window.clipboardData).getData('text');
            var limpio = texto.replace(/[^0-9+\- ]/g, '');
            document.execCommand('insertText', false, limpio);
        });

        /* Validar al salir */
        input.addEventListener('blur', function () {
            var val = input.value.trim();
            if (val === '') { limpiarEstado(input); return; }
            if (!reTel.test(val)) {
                marcarError(input, 'El telefono debe tener entre 6 y 15 digitos. No se permiten letras.');
            } else {
                marcarOk(input);
            }
        });

        input.addEventListener('input', function () {
            if (reTel.test(input.value.trim())) marcarOk(input);
        });
    });

    /* ── DNI: solo digitos ───────────────────────────────────────────── */
    var dniInput = document.getElementById('dni');
    if (dniInput) {
        dniInput.addEventListener('keypress', function (e) {
            if (!/\d/.test(e.key)) { e.preventDefault(); parpadear(dniInput); }
        });
        dniInput.addEventListener('blur', function () {
            var val = dniInput.value.trim();
            if (val === '') { limpiarEstado(dniInput); return; }
            if (!/^\d{8}$/.test(val)) {
                marcarError(dniInput, 'El DNI debe tener exactamente 8 digitos numericos.');
            } else {
                marcarOk(dniInput);
            }
        });
    }

    /* ── Efecto visual al bloquear una tecla ─────────────────────────── */
    function parpadear(input) {
        input.classList.add('border-danger');
        setTimeout(function () { input.classList.remove('border-danger'); }, 300);
    }
});
</script>
@endpush
