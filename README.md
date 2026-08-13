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

## Mapa de la arquitectura MVC

Las tres capas son carpetas hermanas dentro de `app/`, para que la
arquitectura se vea directamente en el árbol de directorios:

```
app/
├── Modelo/              ═══ MODELO ═══
│   ├── *.php                  12 modelos Eloquent
│   ├── Servicios/             reglas de negocio y auditoría
│   └── Excepciones/           errores de regla de negocio
│
├── Vista/               ═══ VISTA ═══
│   ├── *.blade.php            plantillas
│   └── recursos/              SCSS y JavaScript
│
└── Controlador/         ═══ CONTROLADOR ═══
    ├── *.php                  controladores
    ├── Auth/                  acceso al sistema
    ├── Middleware/            control de permisos
    └── Validaciones/          FormRequests

routes/web.php                 enrutamiento (entrada al Controlador)
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
