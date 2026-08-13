{{--
    Capa VISTA - Plantilla maestra del panel administrativo.
    Sprints 1, 2, 3 y 4 operativos. Sprint 5 pendiente.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Panel') · SISARST</title>
    @vite(['app/Vista/recursos/scss/app.scss', 'app/Vista/recursos/js/app.js'])
</head>
<body>
<div class="sisarst-layout">

    <aside class="sisarst-sidebar no-imprimir">
        <div class="sisarst-brand">
            <strong>SISARST</strong>
            <small>Red de Salud Tayacaja</small>
        </div>

        <nav class="nav flex-column mt-2">

            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            <a class="nav-link {{ request()->routeIs('personal.*') ? 'active' : '' }}"
               href="{{ route('personal.index') }}">
                <i class="bi bi-people-fill"></i> Padrón de Personal
            </a>

            <a class="nav-link {{ request()->routeIs('asistencia.*') || request()->routeIs('horario.*') ? 'active' : '' }}"
               href="{{ route('asistencia.index') }}">
                <i class="bi bi-calendar-check"></i> Control Asistencia
            </a>

            <a class="nav-link {{ request()->routeIs('movimiento.*') ? 'active' : '' }}"
               href="{{ route('movimiento.index') }}">
                <i class="bi bi-arrow-left-right"></i> Movimientos
            </a>

            <a class="nav-link {{ request()->routeIs('usuario.*') || request()->routeIs('rol.*') ? 'active' : '' }}"
               href="{{ route('usuario.index') }}">
                <i class="bi bi-person-badge"></i> Gestión de Usuarios
            </a>

            <a class="nav-link {{ request()->routeIs('reporte.*') ? 'active' : '' }}"
               href="{{ route('reporte.index') }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
            </a>

        </nav>

        <div class="mt-auto p-3 small text-white-50">
            v1.0 · Sprint 5<br>
            {{ now()->format('d/m/Y') }}
        </div>
    </aside>

    <div class="sisarst-main">

        <header class="sisarst-topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none js-toggle-menu" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="h5 mb-0">@yield('titulo', 'Panel')</h1>
                    <small class="text-muted">@yield('subtitulo')</small>
                </div>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" type="button">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="text-start lh-sm d-none d-sm-block">
                        <span class="d-block small fw-semibold">{{ auth()->user()->nombre_mostrado }}</span>
                        <span class="d-block text-muted" style="font-size:.72rem">
                            {{ auth()->user()->roles->pluck('nombre')->implode(' · ') ?: 'Sin rol asignado' }}
                        </span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header small">{{ auth()->user()->correo_institucional }}</li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="sisarst-content">
            @include('partials.mensajes')
            @yield('contenido')
        </main>
    </div>
</div>

{{-- Scripts inyectados por las vistas hijas (validaciones, pickers, etc.) --}}
@stack('scripts')
</body>
</html>
