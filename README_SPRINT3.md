# SISARST — Sprint 3: Gestión de Movimientos Institucionales

**Sistema Web Integrado para la Gestión del Personal — Red de Salud Tayacaja**
Arquitectura **MVC** sobre Laravel 12 · PHP 8.2 · MariaDB (puerto 3307) · Bootstrap 5.3

> **Prerrequisito:** los Sprints 1 (Padrón de Personal) y 2 (Control de Asistencia) deben
> estar implementados y operativos. Este sprint añade el módulo de movimientos sobre la misma base.

---

## Alcance del Sprint 3

| HU | RF | Funcionalidad |
|---|---|---|
| HU-08 | RF-08 | Registrar movimiento institucional (comisión, rotación, licencia, permiso, vacación) |
| HU-09 | RF-09 | Consultar movimientos por período, tipo, estado y área con filtros |
| HU-10 | RF-10 | Actualizar el estado del movimiento según la máquina de estados |

---

## Máquina de estados

```
PENDIENTE ──> APROBADO ──> FINALIZADO
    └──────> RECHAZADO
```

| Estado | Descripción |
|---|---|
| `PENDIENTE` | Estado inicial al registrar. Admite edición y transición |
| `APROBADO` | Autorizado por el responsable. El trabajador puede salir |
| `RECHAZADO` | Denegado. Requiere motivo obligatorio. Estado terminal |
| `FINALIZADO` | Periodo concluido. Cierre definitivo. Estado terminal |

**Reglas de negocio clave:**
- Un trabajador no puede tener dos movimientos vigentes que se crucen en el tiempo
- El personal `INACTIVO` no admite nuevos movimientos
- Las rotaciones exigen establecer el área de destino al aprobar (actualiza el padrón)
- Los movimientos aprobados con período vencido deben finalizarse

---

## Cronograma del Sprint 3

| Tarea | Fechas | Responsable |
|---|---|---|
| Sprint Planning | 30/06/2026 | Cristhian Prieto |
| Análisis proceso movimientos | 01/07 – 02/07/2026 | Eduardo Soto Boza |
| Diseño UML módulo movimientos | 03/07 – 05/07/2026 | David Sedano Humani |
| Desarrollo HU-08: Registrar movimientos | 07/07 – 09/07/2026 | Mariano Silva Choque |
| Desarrollo HU-09: Consultar movimientos | 09/07 – 11/07/2026 | Eduardo Soto Boza |
| Desarrollo HU-10: Actualizar estados | 11/07 – 12/07/2026 | Joel Mendoza Taype |
| Pruebas del módulo | 12/07 – 13/07/2026 | David Sedano Humani |
| Sprint Review | 14/07/2026 | Cristhian Prieto |

---

## Mapa de la arquitectura MVC — Sprint 3 (archivos nuevos)

Los archivos de los Sprints 1 y 2 se mantienen. El Sprint 3 agrega:

```
app/
│
├── Modelo/                                  ══ MODELO ══ (nuevos en Sprint 3)
│   ├── MovimientoInstitucional.php          ← HU-08/09/10: maquina de estados, scopes, atributos
│   ├── TipoMovimiento.php                   ← catalogo: comision, rotacion, licencia, permiso, vacacion
│   └── Servicios/
│       └── MovimientoService.php            ← registrar(), actualizar(), cambiarEstado(), finalizarVencidos()
│
├── Vista/                                   ══ VISTA ══ (nuevas en Sprint 3)
│   ├── dashboard.blade.php                  ← tablero Sprint 3: KPIs por estado, pendientes, en curso
│   ├── layouts/
│   │   └── app.blade.php                    ← sidebar actualizado con Movimientos Institucionales
│   └── movimiento/
│       ├── index.blade.php                  ← HU-09: listado con filtros por tipo, estado, area, periodo
│       ├── create.blade.php                 ← HU-08: formulario de alta
│       ├── edit.blade.php                   ← HU-08: edicion de movimiento PENDIENTE
│       ├── show.blade.php                   ← HU-09/HU-10: ficha + maquina de estados + historial
│       └── _formulario.blade.php            ← campos compartidos create y edit
│
└── Controlador/                             ══ CONTROLADOR ══ (nuevos en Sprint 3)
    ├── MovimientoController.php             ← HU-08: create/store/edit/update; HU-09: index/show; HU-10: cambiarEstado
    └── Validaciones/
        ├── MovimientoRequest.php            ← HU-08: validacion de registro y edicion
        └── CambiarEstadoRequest.php         ← HU-10: validacion del cambio de estado

routes/web.php                              ← agrega rutas /movimiento (MOVIMIENTOS.LEER/ESCRIBIR/EDITAR)
```

---

## Tipos de movimiento (catálogo)

| Tipo | Requiere destino | Descripción |
|---|---|---|
| `COMISION_SERVICIO` | Sí | Desplazamiento temporal a otro establecimiento |
| `ROTACION` | Sí | Traslado definitivo (actualiza el padrón al aprobar) |
| `LICENCIA` | No | Ausencia autorizada con goce de haber |
| `PERMISO` | No | Ausencia de corta duración |
| `VACACIONES` | No | Descanso vacacional reglamentario |

---

## Permisos requeridos (tabla `permiso`)

| Permiso | Rutas cubiertas |
|---|---|
| `MOVIMIENTOS.LEER` | GET /movimiento, GET /movimiento/{id} |
| `MOVIMIENTOS.ESCRIBIR` | POST /movimiento, GET/PUT /movimiento/{id}/editar |
| `MOVIMIENTOS.EDITAR` | PATCH /movimiento/{id}/estado, PATCH /finalizar-vencidos |

---

## Advertencias importantes

- **No ejecute `php artisan migrate`.** El esquema se crea con los scripts SQL.
- **Puerto de base de datos: 3307.** Asegúrese que `DB_PORT=3307` en `.env`.
- **`.env` nunca se sube al repositorio.**
- **Un trabajador INACTIVO** no puede tener nuevos movimientos (CA-HU08-02).
- **Las rotaciones aprobadas** actualizan el campo `area_id` del padrón (DS-HU10).
- **Los movimientos finalizados/rechazados** no admiten edición ni cambio de estado.

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
