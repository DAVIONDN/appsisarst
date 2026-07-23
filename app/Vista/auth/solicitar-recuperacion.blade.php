{{--
    Capa VISTA - HU-17 / CA-HU17-01: solicitud del enlace de recuperacion.
    Figuras 4.12 y 4.13 del documento de diseno.

    La pantalla no confirma ni desmiente si el correo esta registrado:
    el mensaje de respuesta es siempre el mismo (CA-HU17-03).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contrasena · SISARST</title>
    @vite(['app/Vista/recursos/scss/app.scss', 'app/Vista/recursos/js/app.js'])
</head>
<body class="d-flex align-items-center" style="background:linear-gradient(135deg,#0b2d4d 0%,#0d5aa7 100%)">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-9 col-md-6 col-lg-5">

            <div class="text-center text-white mb-4">
                <i class="bi bi-envelope-lock display-4"></i>
                <h1 class="h4 mt-2 mb-0 fw-bold">Recuperar contrasena</h1>
                <p class="mb-0 small opacity-75">Sistema SISARST · Red de Salud Tayacaja</p>
            </div>

            <div class="card">
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <p class="small text-muted">
                        Ingrese su <strong>correo institucional</strong>. Si corresponde a una cuenta
                        activa del sistema, le enviaremos un enlace para definir una contrasena nueva.
                    </p>

                    <form method="POST" action="{{ route('recuperacion.enviar') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="correo_institucional" class="form-label obligatorio">
                                Correo institucional
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email"
                                       class="form-control @error('correo_institucional') is-invalid @enderror"
                                       id="correo_institucional" name="correo_institucional"
                                       value="{{ old('correo_institucional') }}"
                                       placeholder="usuario@redsaludtayacaja.gob.pe"
                                       autocomplete="email" autofocus required>
                                @error('correo_institucional')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-light border small">
                            <i class="bi bi-clock me-1"></i>
                            El enlace es de <strong>un solo uso</strong> y vence a los
                            <strong>30 minutos</strong>.
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-send me-1"></i> Enviar enlace de recuperacion
                        </button>
                    </form>

                    <p class="text-center small mt-3 mb-0">
                        <a href="{{ route('login') }}" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Volver al acceso
                        </a>
                    </p>
                </div>
            </div>

            <p class="text-center text-white-50 small mt-3 mb-0">
                Por seguridad, el sistema no revela que correos estan registrados.
            </p>
        </div>
    </div>
</div>

</body>
</html>
