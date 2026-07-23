{{--
    Capa VISTA - HU-17 / CA-HU17-04: definicion de la contrasena nueva.
    Solo se llega aqui con un token vigente; al guardar se vuelve a
    verificar, porque el enlace es de un solo uso (CA-HU17-02).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva contrasena · SISARST</title>
    @vite(['app/Vista/recursos/scss/app.scss', 'app/Vista/recursos/js/app.js'])
</head>
<body class="d-flex align-items-center" style="background:linear-gradient(135deg,#0b2d4d 0%,#0d5aa7 100%)">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-9 col-md-6 col-lg-5">

            <div class="text-center text-white mb-4">
                <i class="bi bi-shield-lock display-4"></i>
                <h1 class="h4 mt-2 mb-0 fw-bold">Nueva contrasena</h1>
                <p class="mb-0 small opacity-75">{{ $usuario->correo_institucional }}</p>
            </div>

            <div class="card">
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-clock me-1"></i>
                        Este enlace vence en <strong>{{ $minutosRestantes }} minuto(s)</strong>
                        y solo puede usarse una vez.
                    </div>

                    <form method="POST" action="{{ route('recuperacion.restablecer') }}" novalidate>
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="password" class="form-label obligatorio">Contrasena nueva</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password"
                                       autocomplete="new-password" autofocus required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Minimo 8 caracteres, con letras y numeros.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label obligatorio">
                                Confirmar contrasena
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control"
                                       id="password_confirmation" name="password_confirmation"
                                       autocomplete="new-password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-check2-circle me-1"></i> Guardar contrasena
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-white-50 small mt-3 mb-0">
                Su contrasena se guarda cifrada; el sistema nunca la almacena en texto claro.
            </p>
        </div>
    </div>
</div>

</body>
</html>
