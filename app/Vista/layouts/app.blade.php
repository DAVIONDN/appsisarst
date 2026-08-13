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

            {{-- ── Dashboard ───────────────────────────────────────────── --}}
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            {{-- ── Sprint 1: Padrón de Personal ────────────────────────── --}}
            @php($s1 = request()->routeIs('personal.*'))
            <button class="nav-link sisarst-nav-toggle {{ $s1 ? 'active' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav-s1"
                    aria-expanded="{{ $s1 ? 'true' : 'false' }}">
                <i class="bi bi-people-fill"></i>
                <span>Padrón de Personal</span>
                <i class="bi bi-chevron-down sisarst-chevron ms-auto"></i>
            </button>
            <div class="collapse {{ $s1 ? 'show' : '' }}" id="nav-s1">
                <div class="sisarst-submenu">
                    <a class="nav-link {{ request()->routeIs('personal.*') ? 'active' : '' }}"
                       href="{{ route('personal.index') }}">
                        <i class="bi bi-person-lines-fill"></i> Padrón
                    </a>
                </div>
            </div>

            {{-- ── Sprint 2: Control de Asistencia ─────────────────────── --}}
            @php($s2 = request()->routeIs('asistencia.*') || request()->routeIs('horario.*'))
            <button class="nav-link sisarst-nav-toggle {{ $s2 ? 'active' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav-s2"
                    aria-expanded="{{ $s2 ? 'true' : 'false' }}">
                <i class="bi bi-calendar-check"></i>
                <span>Control Asistencia</span>
                <i class="bi bi-chevron-down sisarst-chevron ms-auto"></i>
            </button>
            <div class="collapse {{ $s2 ? 'show' : '' }}" id="nav-s2">
                <div class="sisarst-submenu">
                    <a class="nav-link {{ request()->routeIs('asistencia.*') ? 'active' : '' }}"
                       href="{{ route('asistencia.index') }}">
                        <i class="bi bi-calendar-check"></i> Asistencia
                    </a>
                    <a class="nav-link {{ request()->routeIs('horario.*') ? 'active' : '' }}"
                       href="{{ route('horario.index') }}">
                        <i class="bi bi-clock"></i> Horarios de Trabajo
                    </a>
                </div>
            </div>

            {{-- ── Sprint 3: Movimientos ────────────────────────────────── --}}
            @php($s3 = request()->routeIs('movimiento.*'))
            <button class="nav-link sisarst-nav-toggle {{ $s3 ? 'active' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav-s3"
                    aria-expanded="{{ $s3 ? 'true' : 'false' }}">
                <i class="bi bi-arrow-left-right"></i>
                <span>Movimientos</span>
                <i class="bi bi-chevron-down sisarst-chevron ms-auto"></i>
            </button>
            <div class="collapse {{ $s3 ? 'show' : '' }}" id="nav-s3">
                <div class="sisarst-submenu">
                    <a class="nav-link {{ request()->routeIs('movimiento.*') ? 'active' : '' }}"
                       href="{{ route('movimiento.index') }}">
                        <i class="bi bi-arrow-left-right"></i> Movimientos Institucionales
                    </a>
                </div>
            </div>

            {{-- ── Sprint 4: Gestión de Usuarios ───────────────────────── --}}
            @php($s4 = request()->routeIs('usuario.*') || request()->routeIs('rol.*'))
            <button class="nav-link sisarst-nav-toggle {{ $s4 ? 'active' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav-s4"
                    aria-expanded="{{ $s4 ? 'true' : 'false' }}">
                <i class="bi bi-person-badge"></i>
                <span>Gestión de Usuarios</span>
                <i class="bi bi-chevron-down sisarst-chevron ms-auto"></i>
            </button>
            <div class="collapse {{ $s4 ? 'show' : '' }}" id="nav-s4">
                <div class="sisarst-submenu">
                    <a class="nav-link {{ request()->routeIs('usuario.*') ? 'active' : '' }}"
                       href="{{ route('usuario.index') }}">
                        <i class="bi bi-person-badge"></i> Usuarios del Sistema
                    </a>
                    <a class="nav-link {{ request()->routeIs('rol.*') ? 'active' : '' }}"
                       href="{{ route('rol.index') }}">
                        <i class="bi bi-shield-lock"></i> Roles y Permisos
                    </a>
                </div>
            </div>

            {{-- ── Sprint 5: Reportes ───────────────────────────────────── --}}
            @php($s5 = request()->routeIs('reporte.*'))
            <button class="nav-link sisarst-nav-toggle {{ $s5 ? 'active' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav-s5"
                    aria-expanded="{{ $s5 ? 'true' : 'false' }}">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reportes</span>
                <i class="bi bi-chevron-down sisarst-chevron ms-auto"></i>
            </button>
            <div class="collapse {{ $s5 ? 'show' : '' }}" id="nav-s5">
                <div class="sisarst-submenu">
                    <a class="nav-link {{ request()->routeIs('reporte.*') ? 'active' : '' }}"
                       href="{{ route('reporte.index') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                    </a>
                </div>
            </div>

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
