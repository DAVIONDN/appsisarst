# SISARST — Sprint 1: Gestión del Padrón de Personal

**Sistema Web Integrado para la Gestión del Personal — Red de Salud Tayacaja**
Arquitectura **MVC** sobre Laravel 12 · PHP 8.2 · MariaDB (puerto 3307) · Bootstrap 5.3

---

## Alcance de este sprint

| HU | RF | Funcionalidad |
|---|---|---|
| HU-01 | RF-01 | Registrar personal |
| HU-02 | RF-02 | Editar datos del personal |
| HU-03 | RF-03 | Consultar el padrón con filtros |
| HU-04 | RF-04 | Desactivar personal (baja lógica) |
| HU-18 | RF-20 | Consultar el historial laboral |
| — | RF-13 | Acceso al sistema (base para el control por rol) |

Los sprints 2 a 5 (asistencia, movimientos, usuarios y reportes) **no** están
implementados; sus entradas del menú aparecen deshabilitadas.

---

## Puesta en marcha rápida

```bash
# 1. Dependencias
php C:\composer\composer.phar install
npm install

# 2. Base de datos: ejecutar en MySQL Workbench, en este orden
#    docs/04_BASE_DATOS/01_crear_base_datos_MYSQL.sql
#    docs/04_BASE_DATOS/02_datos_iniciales_MYSQL.sql
#    docs/04_BASE_DATOS/03_usuarios_demo_MYSQL.sql

# 3. Configuración
copy .env.example .env
php artisan key:generate
#    editar .env y completar DB_PASSWORD

# 4. Recursos del frontend
npm run build

# 5. Servidor
php artisan serve --port=8080
```

Abrir <http://127.0.0.1:8080>.

### Cuentas de demostración

Contraseña única: **`Sisarst2026$`**

| Usuario | Rol | Alcance |
|---|---|---|
| `mquispe` | ADMIN_RRHH | Padrón completo |
| `cramos` | ADMIN_SISTEMA | Todo el sistema |
| `rhuaman` | JEFE_AREA | Solo consulta, solo su área |
| `apaucar` | GERENTE_RED | Solo consulta |

---

## Mapa de la arquitectura MVC — Sprint 1

Las tres capas son carpetas hermanas dentro de `app/`, para que la
arquitectura se vea directamente en el árbol de directorios:

```
app/
│
├── Modelo/                          ══ MODELO ══
│   ├── Personal.php                 ← entidad principal del Sprint 1 (HU-01…HU-04, HU-18)
│   ├── Area.php                     ← área de trabajo del trabajador
│   ├── Establecimiento.php          ← unidad de salud que contiene el área
│   ├── Horario.php                  ← horario asignado al personal
│   ├── Rol.php                      ← roles: ADMIN_RRHH, JEFE_AREA, GERENTE_RED…
│   ├── Permiso.php                  ← permisos: PADRON.LEER, PADRON.ESCRIBIR…
│   ├── Usuario.php                  ← cuenta de acceso vinculada al Personal
│   ├── LogAuditoria.php             ← trazabilidad de cambios (CA-HU02-04)
│   ├── Asistencia.php               ← marcaciones (Sprint 2)
│   ├── MovimientoInstitucional.php  ← traslados (Sprint 3)
│   ├── TipoMovimiento.php           ← catálogo de tipos de movimiento
│   ├── Reporte.php                  ← reportes (Sprint 5)
│   ├── Servicios/                   ← reglas de negocio y auditoría
│   │   ├── PadronService.php        ← registrar(), actualizar(), desactivar(), reactivar()
│   │   └── AuditoriaService.php     ← guarda entradas en log_auditoria
│   └── Excepciones/                 ← errores de regla de negocio
│       └── ReglaNegocioException.php
│
├── Vista/                           ══ VISTA ══
│   ├── layouts/
│   │   └── app.blade.php            ← plantilla maestra (sidebar, topbar, mensajes)
│   ├── partials/
│   │   └── mensajes.blade.php       ← alertas de éxito, error y acceso restringido
│   ├── auth/
│   │   └── login.blade.php          ← pantalla de inicio de sesión (RF-13)
│   ├── dashboard.blade.php          ← tablero con KPIs y últimas incorporaciones
│   ├── personal/                    ← vistas del módulo Padrón de Personal
│   │   ├── index.blade.php          ← HU-03: listado paginado con filtros
│   │   ├── create.blade.php         ← HU-01: formulario de alta
│   │   ├── edit.blade.php           ← HU-02: formulario de edición
│   │   ├── show.blade.php           ← HU-18: historial laboral / ficha individual
│   │   └── _formulario.blade.php    ← campos compartidos HU-01 y HU-02 + validación JS
│   └── recursos/                    ← SCSS y JavaScript (compilados con Vite)
│
└── Controlador/                     ══ CONTROLADOR ══
    ├── PersonalController.php        ← HU-01…HU-04, HU-18: index, create, store, edit…
    ├── DashboardController.php       ← tablero principal con KPIs
    ├── Auth/                         ← acceso al sistema
    │   └── LoginController.php       ← login / logout (RF-13)
    ├── Middleware/                   ← control de permisos
    │   └── VerificarPermiso.php      ← evalúa módulo+acción contra la tabla rol_permiso
    └── Validaciones/                 ← FormRequests
        ├── PersonalRequest.php       ← DNI, nombres, apellidos, cargo, teléfono, correo
        ├── DesactivarPersonalRequest.php  ← motivo de baja (HU-04)
        └── LoginRequest.php          ← credenciales de acceso

routes/web.php                       ← enrutamiento (entrada al Controlador) + Route::fallback()
```

> Esta disposición **no** es la convención de Laravel, que reparte las capas
> entre `app/Models`, `app/Http` y `resources/views`. Se adoptó por decisión
> del equipo para que la arquitectura MVC sea evidente. El framework lo
> soporta sin problema: `config/view.php` apunta a `app/Vista` y el
> autocargador PSR-4 resuelve los namespaces `App\Modelo`, `App\Vista` y
> `App\Controlador`. La carpeta `resources/` ya no se usa.

Detalle completo en `docs/05_IMPLEMENTACION_MVC/01_ARQUITECTURA_MVC.md`.

---

## Advertencias importantes

- **No ejecute `php artisan migrate`.** El esquema se crea con los scripts SQL.
  Regenerarlo con migraciones destruiría los 5 disparadores, las restricciones
  `CHECK` y los tipos `ENUM` del modelo entidad-relación aprobado.
  La carpeta `database/migrations` está vacía a propósito.
- **Puerto de base de datos: 3307.** El proyecto usa MariaDB de XAMPP en el
  puerto 3307 (no el 3306 por defecto). Asegúrese que `DB_PORT=3307` en `.env`.
- **`.env` nunca se sube al repositorio.**

---

## Pruebas

```bash
php artisan test
```

Requiere la base `red_salud_tayacaja_test`, creada con los mismos scripts SQL
(ver `docs/05_IMPLEMENTACION_MVC/04_PLAN_PRUEBAS_SPRINT1.md`).

---

## Documentación

| Documento | Contenido |
|---|---|
| `docs/05_IMPLEMENTACION_MVC/01_ARQUITECTURA_MVC.md` | Arquitectura, capas, decisiones y desviaciones |
| `docs/05_IMPLEMENTACION_MVC/02_MANUAL_INSTALACION.md` | Instalación paso a paso y solución de problemas |
| `docs/05_IMPLEMENTACION_MVC/03_TRAZABILIDAD_SPRINT1.md` | RF → HU → criterio de aceptación → archivo |
| `docs/05_IMPLEMENTACION_MVC/04_PLAN_PRUEBAS_SPRINT1.md` | Casos de prueba manuales y automatizados |

---

## Equipo

| Rol Scrum | Integrante |
|---|---|
| Product Owner | Cristhian Prieto Hinojosa |
| Scrum Master | Lisbeth Sonia Quispe Huaripata |
| Development Team | Mariano Silva, Joel Mendoza, David Sedano, Eduardo Soto |

Universidad Nacional de Huancavelica · Ingeniería de Sistemas · Calidad de Software · 2026
