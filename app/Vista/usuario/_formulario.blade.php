{{--
    Capa VISTA - Campos compartidos por el alta y la edicion de cuentas.
    CA-HU11-01: la cuenta se asocia a un personal del padron.
    CA-HU12-01: se asignan los roles en el mismo formulario.
--}}
@php($u = $usuario ?? null)
@php($esAlta = $u === null)

<div class="row g-3">

    <div class="col-12">
        <h2 class="h6 text-primary border-bottom pb-2 mb-0">
            <i class="bi bi-person-vcard me-1"></i> Identificacion
        </h2>
    </div>

    <div class="col-12 col-md-6">
        <label for="personal_id" class="form-label obligatorio">Trabajador del padron</label>
        <select class="form-select @error('personal_id') is-invalid @enderror"
                id="personal_id" name="personal_id" required>
            <option value="">-- Seleccione --</option>
            @foreach ($personal as $p)
                <option value="{{ $p->personal_id }}"
                    @selected((string) old('personal_id', $u?->personal_id) === (string) $p->personal_id)>
                    {{ $p->nombre_completo }} — {{ $p->dni }} ({{ $p->area?->nombre }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Solo aparece el personal activo que aun no tiene cuenta.</div>
        @error('personal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="correo_institucional" class="form-label obligatorio">Correo institucional</label>
        <input type="email" maxlength="120"
               class="form-control @error('correo_institucional') is-invalid @enderror"
               id="correo_institucional" name="correo_institucional"
               value="{{ old('correo_institucional', $u?->correo_institucional) }}" required>
        <div class="form-text">A esta direccion se envia el enlace de recuperacion (HU-17).</div>
        @error('correo_institucional') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 mt-4">
        <h2 class="h6 text-primary border-bottom pb-2 mb-0">
            <i class="bi bi-key me-1"></i> Credenciales
        </h2>
    </div>

    <div class="col-12 col-md-4">
        <label for="username" class="form-label obligatorio">Nombre de usuario</label>
        <input type="text" maxlength="40" minlength="4"
               class="form-control font-monospace @error('username') is-invalid @enderror"
               id="username" name="username" value="{{ old('username', $u?->username) }}" required>
        <div class="form-text">Minimo 4 caracteres: minusculas, numeros, punto, guion.</div>
        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($esAlta)
        <div class="col-12 col-md-4">
            <label for="password" class="form-label obligatorio">Contrasena temporal</label>
            <input type="password"
                   class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password" required autocomplete="new-password">
            <div class="form-text">Minimo 8 caracteres con letras y numeros.</div>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label for="password_confirmation" class="form-label obligatorio">Confirmar contrasena</label>
            <input type="password" class="form-control"
                   id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>
    @else
        <div class="col-12 col-md-8 d-flex align-items-end">
            <div class="alert alert-light border small mb-0 w-100">
                <i class="bi bi-info-circle me-1"></i>
                La contrasena no se edita aqui. Use
                <a href="{{ route('usuario.clave.form', $u) }}">Restablecer contrasena</a>,
                o deje que el usuario la recupere por correo (HU-17).
            </div>
        </div>
    @endif

    <div class="col-12 mt-4">
        <h2 class="h6 text-primary border-bottom pb-2 mb-0">
            <i class="bi bi-shield-check me-1"></i> Roles (CA-HU12-01)
        </h2>
    </div>

    <div class="col-12">
        @error('roles') <div class="alert alert-danger py-2 small">{{ $message }}</div> @enderror

        <div class="row g-2">
            @php($seleccionados = old('roles', $u?->roles->pluck('rol_id')->all() ?? []))
            @foreach ($roles as $rol)
                <div class="col-12 col-md-6">
                    <div class="border rounded p-2 h-100">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="roles[]"
                                   value="{{ $rol->rol_id }}" id="rol{{ $rol->rol_id }}"
                                   @checked(in_array($rol->rol_id, (array) $seleccionados))>
                            <label class="form-check-label fw-semibold" for="rol{{ $rol->rol_id }}">
                                {{ $rol->nombre }}
                            </label>
                        </div>
                        <div class="small text-muted">{{ $rol->descripcion }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="form-text mt-2">
            Una cuenta debe tener al menos un rol: sin rol puede iniciar sesion pero
            no accede a ningun modulo.
        </div>
    </div>
</div>
