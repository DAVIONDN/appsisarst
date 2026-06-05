{{--
    Capa VISTA - Pantalla de acceso (RF-13).
    Corresponde a la Figura 4.11 del documento de diseno.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso · SISARST</title>
    @vite(['app/Vista/recursos/scss/app.scss', 'app/Vista/recursos/js/app.js'])
</head>
<body class="d-flex align-items-center" style="background:linear-gradient(135deg,#0b2d4d 0%,#0d5aa7 100%)">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-9 col-md-6 col-lg-4">

            <div class="text-center text-white mb-4">
                <i class="bi bi-hospital display-4"></i>
                <h1 class="h3 mt-2 mb-0 fw-bold" style="letter-spacing:.1em">SISARST</h1>
                <p class="mb-0 small opacity-75">
                    Sistema Web Integrado de Gestion del Personal<br>
                    Red de Salud Tayacaja
                </p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 text-muted mb-3">Ingrese sus credenciales</h2>

                    @if (session('exito'))
                        <div class="alert alert-success py-2 small">{{ session('exito') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label obligatorio">Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text"
                                       class="form-control @error('username') is-invalid @enderror"
                                       id="username" name="username"
                                       value="{{ old('username') }}"
                                       autocomplete="username" autofocus required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label obligatorio">Contrasena</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password"
                                       autocomplete="current-password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                        </button>
                    </form>

                    <p class="text-center text-muted small mt-3 mb-0">
                        {{-- HU-17 se implementa en el Sprint 4 --}}
                        <span class="text-decoration-none opacity-50">
                            Olvide mi contrasena (disponible en el Sprint 4)
                        </span>
                    </p>
                </div>
            </div>

            <p class="text-center text-white-50 small mt-3 mb-0">
                Ley N.° 29733 de Proteccion de Datos Personales · Uso institucional
            </p>
        </div>
    </div>
</div>

</body>
</html>
