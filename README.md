# SISARST — Sistema Web Integrado de Gestión del Personal

**Red de Salud Tayacaja**
Arquitectura **MVC** sobre Laravel 12 · PHP 8.2 · MySQL (puerto 3306) · Bootstrap 5.3

---

## Documentación por Sprint

| Sprint | Módulo | Documento |
|---|---|---|
| Sprint 1 | Gestión del Padrón de Personal | [README_SPRINT1.md](README_SPRINT1.md) |
| Sprint 2 | Control de Asistencia | [README_SPRINT2.md](README_SPRINT2.md) |
| Sprint 3 | Movimientos Institucionales | [README_SPRINT3.md](README_SPRINT3.md) |
| Sprint 4 | Usuarios y Roles | _pendiente_ |
| Sprint 5 | Reportes | _pendiente_ |

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

| Usuario | Rol |
|---|---|
| `mquispe` | ADMIN_RRHH |
| `cramos` | ADMIN_SISTEMA |
| `rhuaman` | JEFE_AREA |
| `apaucar` | GERENTE_RED |

---

## Advertencias importantes

- **No ejecute `php artisan migrate`.** El esquema se crea con los scripts SQL.
  La carpeta `database/migrations` está vacía a propósito.
- **Puerto de base de datos: 3307.** Asegúrese que `DB_PORT=3307` en `.env`.
- **`.env` nunca se sube al repositorio.**

---

## Equipo

| Rol Scrum | Integrante |
|---|---|
| Product Owner | Cristhian Prieto Hinojosa |
| Scrum Master | Lisbeth Sonia Quispe Huaripata |
| Development Team | Mariano Silva, Joel Mendoza, David Sedano, Eduardo Soto |

Universidad Nacional de Huancavelica · Ingeniería de Sistemas · Calidad de Software · 2026
