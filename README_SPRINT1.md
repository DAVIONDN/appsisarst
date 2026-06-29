# SISARST — Sprint 1: Gestión del Padrón de Personal

**Sistema Web Integrado para la Gestión del Personal — Red de Salud Tayacaja**
Arquitectura **MVC** sobre Laravel 12 · PHP 8.2 · MariaDB (puerto 3307) · Bootstrap 5.3

---

## Alcance del Sprint 1

| HU | RF | Funcionalidad |
|---|---|---|
| HU-01 | RF-01 | Registrar personal |
| HU-02 | RF-02 | Editar datos del personal |
| HU-03 | RF-03 | Consultar el padrón con filtros |
| HU-04 | RF-04 | Desactivar personal (baja lógica) |
| HU-18 | RF-20 | Consultar el historial laboral |
| — | RF-13 | Acceso al sistema (autenticación y control por rol) |

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

```
app/
│
├── Modelo/                              ══ MODELO ══
│   ├── Personal.php                     ← HU-01 / HU-02 / HU-03 / HU-04 / HU-18
│   ├── Area.php                         ← área de trabajo del trabajador
│   ├── Establecimiento.php              ← unidad de salud que contiene el área
│   ├── Rol.php                          ← roles: ADMIN_RRHH, JEFE_AREA, GERENTE_RED…
│   ├── Permiso.php                      ← permisos: PADRON.LEER, PADRON.ESCRIBIR…
│   ├── Usuario.php                      ← cuenta de acceso vinculada al Personal
│   ├── LogAuditoria.php                 ← trazabilidad de cambios (HU-02 / HU-04)
│   ├── Servicios/
│   │   ├── PadronService.php            ← registrar(), actualizar(), desactivar(), reactivar()
│   │   └── AuditoriaService.php         ← registra entradas en log_auditoria
│   └── Excepciones/
│       └── ReglaNegocioException.php    ← error de regla de negocio del dominio
│
├── Vista/                               ══ VISTA ══
│   ├── layouts/
│   │   └── app.blade.php                ← plantilla maestra (sidebar, topbar, mensajes)
│   ├── partials/
│   │   └── mensajes.blade.php           ← alertas de éxito, error y acceso restringido
│   ├── auth/
│   │   └── login.blade.php              ← pantalla de inicio de sesión (RF-13)
│   ├── dashboard.blade.php              ← tablero con KPIs y últimas incorporaciones
│   └── personal/
│       ├── index.blade.php              ← HU-03: listado paginado con filtros
│       ├── create.blade.php             ← HU-01: formulario de alta
│       ├── edit.blade.php               ← HU-02: formulario de edición
│       ├── show.blade.php               ← HU-18: historial laboral / ficha individual
│       └── _formulario.blade.php        ← campos compartidos HU-01 y HU-02
│
└── Controlador/                         ══ CONTROLADOR ══
    ├── PersonalController.php            ← HU-01 / HU-02 / HU-03 / HU-04 / HU-18
    ├── DashboardController.php           ← tablero principal con KPIs del padrón
    ├── Auth/
    │   └── LoginController.php           ← login / logout (RF-13)
    ├── Middleware/
    │   └── VerificarPermiso.php          ← evalúa módulo + acción contra rol_permiso
    └── Validaciones/
        ├── PersonalRequest.php           ← HU-01 / HU-02: DNI, nombres, cargo, teléfono…
        ├── DesactivarPersonalRequest.php ← HU-04: motivo de baja obligatorio
        └── LoginRequest.php              ← credenciales de acceso al sistema

routes/web.php                           ← enrutamiento Sprint 1 + Route::fallback()
```

> Esta disposición **no** es la convención de Laravel, que reparte las capas
> entre `app/Models`, `app/Http` y `resources/views`. Se adoptó por decisión
> del equipo para que la arquitectura MVC sea evidente. El framework lo
> soporta sin problema: `config/view.php` apunta a `app/Vista` y el
> autocargador PSR-4 resuelve los namespaces `App\Modelo`, `App\Vista` y
> `App\Controlador`.

---

## Advertencias importantes

- **No ejecute `php artisan migrate`.** El esquema se crea con los scripts SQL.
  La carpeta `database/migrations` está vacía a propósito.
- **Puerto de base de datos: 3307.** Asegúrese que `DB_PORT=3307` en `.env`.
- **`.env` nunca se sube al repositorio.**

---

## Pruebas

```bash
php artisan test
```

---

## Equipo

| Rol Scrum | Integrante |
|---|---|
| Product Owner | Cristhian Prieto Hinojosa |
| Scrum Master | Lisbeth Sonia Quispe Huaripata |
| Development Team | Mariano Silva, Joel Mendoza, David Sedano, Eduardo Soto |

Universidad Nacional de Huancavelica · Ingeniería de Sistemas · Calidad de Software · 2026
