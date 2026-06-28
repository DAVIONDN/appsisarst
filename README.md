# SISARST — Sprint 1 y Sprint 2

**Sistema Web Integrado para la Gestión del Personal — Red de Salud Tayacaja**
Arquitectura **MVC** sobre Laravel 12 · PHP 8.2 · MariaDB (puerto 3307) · Bootstrap 5.3

---

## Sprint 1: Gestión del Padrón de Personal

| HU | RF | Funcionalidad |
|---|---|---|
| HU-01 | RF-01 | Registrar personal |
| HU-02 | RF-02 | Editar datos del personal |
| HU-03 | RF-03 | Consultar el padrón con filtros |
| HU-04 | RF-04 | Desactivar personal (baja lógica) |
| HU-18 | RF-20 | Consultar el historial laboral |
| — | RF-13 | Acceso al sistema (autenticación y control por rol) |

## Sprint 2: Control de Asistencia

| HU | RF | Funcionalidad |
|---|---|---|
| HU-05 | RF-05 | Registrar asistencia del personal (marcación entrada/salida) |
| HU-06 | RF-06 | Consultar asistencia por período con filtros |
| HU-07 | RF-07 | Registro automático de tardanzas y faltas (tarea 23:30) |
| HU-16 | RF-16 | Registro y asignación de horarios de trabajo |

Los sprints 3 a 5 (movimientos, usuarios y reportes) **no** están
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

## Mapa de la arquitectura MVC — Sprint 1 y Sprint 2

Las tres capas son carpetas hermanas dentro de `app/`, para que la
arquitectura se vea directamente en el árbol de directorios:

```
app/
│
├── Modelo/                              ══ MODELO ══
│   ├── Personal.php                     ← HU-01 / HU-02 / HU-03 / HU-04 / HU-18
│   ├── Area.php                         ← área de trabajo del trabajador
│   ├── Establecimiento.php              ← unidad de salud que contiene el área
│   ├── Horario.php                      ← horario asignado al personal
│   ├── Rol.php                          ← roles: ADMIN_RRHH, JEFE_AREA, GERENTE_RED…
│   ├── Permiso.php                      ← permisos: PADRON.LEER, PADRON.ESCRIBIR…
│   ├── Usuario.php                      ← cuenta de acceso vinculada al Personal
│   ├── LogAuditoria.php                 ← trazabilidad de cambios (HU-02 / HU-04)
│   ├── Asistencia.php                   ← HU-05 / HU-06 / HU-07: jornada diaria del personal
│   ├── Servicios/
│   │   ├── PadronService.php            ← registrar(), actualizar(), desactivar(), reactivar()
│   │   ├── AuditoriaService.php         ← registra entradas en log_auditoria
│   │   ├── AsistenciaService.php        ← registrar(), cerrarJornada() (HU-05)
│   │   ├── HorarioService.php           ← crear/editar horarios, asignar al personal (HU-16)
│   │   └── CierreJornadaService.php     ← genera faltas/tardanzas automaticas (HU-07)
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
│   ├── personal/
│   │   ├── index.blade.php              ← HU-03: listado paginado con filtros
│   │   ├── create.blade.php             ← HU-01: formulario de alta
│   │   ├── edit.blade.php               ← HU-02: formulario de edición
│   │   ├── show.blade.php               ← HU-18: historial laboral / ficha individual
│   │   └── _formulario.blade.php        ← campos compartidos HU-01 y HU-02
│   ├── asistencia/
│   │   ├── index.blade.php              ← HU-06: consulta por periodo con filtros
│   │   ├── create.blade.php             ← HU-05: marcacion de entrada / salida
│   │   └── edit.blade.php               ← corrección manual de jornada registrada
│   └── horario/
│       ├── index.blade.php              ← HU-16: listado de horarios activos/inactivos
│       ├── create.blade.php             ← HU-16: crear nuevo horario
│       ├── edit.blade.php               ← HU-16: editar horario existente
│       ├── asignar.blade.php            ← HU-16: asignacion masiva a personal
│       └── _formulario.blade.php        ← campos compartidos create y edit
│
└── Controlador/                         ══ CONTROLADOR ══
    ├── PersonalController.php            ← HU-01 / HU-02 / HU-03 / HU-04 / HU-18
    ├── AsistenciaController.php          ← HU-05 / HU-06: marcacion y consulta de asistencia
    ├── HorarioController.php             ← HU-16: CRUD de horarios + asignacion al personal
    ├── DashboardController.php           ← tablero principal con KPIs
    ├── Auth/
    │   └── LoginController.php           ← login / logout (RF-13)
    ├── Consola/
    │   └── CerrarJornadaCommand.php      ← HU-07: comando artisan de cierre automatico
    ├── Middleware/
    │   └── VerificarPermiso.php          ← evalúa módulo + acción contra rol_permiso
    └── Validaciones/
        ├── PersonalRequest.php           ← HU-01 / HU-02: DNI, nombres, cargo, teléfono…
        ├── DesactivarPersonalRequest.php ← HU-04: motivo de baja obligatorio
        ├── MarcacionRequest.php          ← HU-05: validacion de marcacion de asistencia
        ├── CorregirAsistenciaRequest.php ← corrección manual de jornada
        ├── HorarioRequest.php            ← HU-16: nombre, entrada, salida, tolerancia
        ├── AsignarHorarioRequest.php     ← HU-16: asignacion masiva a lista de personal
        └── LoginRequest.php              ← credenciales de acceso al sistema

routes/web.php                           ← enrutamiento Sprint 1 + Sprint 2 + Route::fallback()
routes/console.php                       ← tarea programada HU-07 (cierre jornada 23:30)
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
