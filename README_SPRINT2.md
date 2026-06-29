# SISARST — Sprint 2: Control de Asistencia

**Sistema Web Integrado para la Gestión del Personal — Red de Salud Tayacaja**
Arquitectura **MVC** sobre Laravel 12 · PHP 8.2 · MariaDB (puerto 3307) · Bootstrap 5.3

> **Prerrequisito:** el Sprint 1 (Padrón de Personal) debe estar implementado
> y operativo. Este sprint añade nuevos módulos sobre la misma base.

---

## Alcance del Sprint 2

| HU | RF | Funcionalidad |
|---|---|---|
| HU-05 | RF-05 | Registrar asistencia del personal (marcación entrada/salida) |
| HU-06 | RF-06 | Consultar asistencia por período con filtros |
| HU-07 | RF-07 | Registro automático de tardanzas y faltas (tarea 23:30) |
| HU-16 | RF-16 | Registro y asignación de horarios de trabajo |

---

## Estados de asistencia

| Estado | Condición |
|---|---|
| `PUNTUAL` | Entrada registrada antes de la hora límite, sin tardanza |
| `TARDANZA` | Entrada registrada después de la hora límite del horario |
| `FALTA` | Sin marcación al cierre de jornada (generada por HU-07) |
| `JUSTIFICADO` | Falta con documento de justificación registrado manualmente |

Un trabajador sin horario asignado **no puede ser evaluado** (criterio CA-HU16-03).

---

## Tarea programada (HU-07)

```bash
# Ejecutar manualmente para pruebas
php artisan jornada:cerrar

# Programación automática en producción
# routes/console.php → dailyAt('23:30') timezone America/Lima
```

---

## Mapa de la arquitectura MVC — Sprint 2 (archivos nuevos)

Los archivos del Sprint 1 se mantienen. El Sprint 2 agrega:

```
app/
│
├── Modelo/                              ══ MODELO ══ (nuevos en Sprint 2)
│   ├── Horario.php                      ← horario asignado al personal (HU-16)
│   ├── Asistencia.php                   ← HU-05 / HU-06 / HU-07: jornada diaria
│   └── Servicios/
│       ├── AsistenciaService.php        ← registrar(), cerrarJornada() (HU-05)
│       ├── HorarioService.php           ← crear/editar horarios, asignar (HU-16)
│       └── CierreJornadaService.php     ← genera faltas/tardanzas automáticas (HU-07)
│
├── Vista/                               ══ VISTA ══ (nuevas en Sprint 2)
│   ├── asistencia/
│   │   ├── index.blade.php              ← HU-06: consulta por periodo con filtros
│   │   ├── create.blade.php             ← HU-05: marcación de entrada / salida
│   │   └── edit.blade.php               ← corrección manual de jornada registrada
│   └── horario/
│       ├── index.blade.php              ← HU-16: listado de horarios activos/inactivos
│       ├── create.blade.php             ← HU-16: crear nuevo horario
│       ├── edit.blade.php               ← HU-16: editar horario existente
│       ├── asignar.blade.php            ← HU-16: asignación masiva a personal
│       └── _formulario.blade.php        ← campos compartidos create y edit
│
└── Controlador/                         ══ CONTROLADOR ══ (nuevos en Sprint 2)
    ├── AsistenciaController.php          ← HU-05 / HU-06: marcación y consulta
    ├── HorarioController.php             ← HU-16: CRUD de horarios + asignación
    ├── Consola/
    │   └── CerrarJornadaCommand.php      ← HU-07: comando artisan de cierre automático
    └── Validaciones/
        ├── MarcacionRequest.php          ← HU-05: validación de marcación
        ├── CorregirAsistenciaRequest.php ← corrección manual de jornada
        ├── HorarioRequest.php            ← HU-16: nombre, entrada, salida, tolerancia
        └── AsignarHorarioRequest.php     ← HU-16: asignación masiva a personal

routes/web.php                           ← agrega rutas /asistencia y /horario
routes/console.php                       ← tarea programada HU-07 (cierre 23:30)
```

---

## Advertencias importantes

- **No ejecute `php artisan migrate`.** El esquema se crea con los scripts SQL.
- **Puerto de base de datos: 3307.** Asegúrese que `DB_PORT=3307` en `.env`.
- **`.env` nunca se sube al repositorio.**
- **Un trabajador sin horario asignado** no genera asistencia automática (CA-HU16-03).
  Asigne horario desde `Horarios de Trabajo → botón Asignar`.

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
