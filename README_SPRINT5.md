# Sprint 5 – Reportes Administrativos

**SISARST** · Sistema Web Integrado de Gestión del Personal  
**Red de Salud Tayacaja**

---

## Descripción

Sprint 5 implementa el módulo de **Reportes Administrativos**, que incluye:

- **HU-13**: Reporte del Padrón de Personal con filtros y exportación
- **HU-14**: Reporte de Asistencia consolidado por trabajador
- **HU-15**: Reporte de Movimientos Institucionales con totales por tipo
- **RF-18**: Exportación a PDF (dompdf) y Excel (PhpSpreadsheet) — Patrón Estrategia

---

## Historias de Usuario

### HU-13: Reporte de Personal (RF-15)

Genera un reporte del padrón de personal con filtros por establecimiento, área, cargo, condición laboral y estado.

**Criterios de aceptación:**
- CA-HU13-01: filtros combinables por área, cargo, condición y establecimiento
- CA-HU13-02: datos coinciden con los registros vigentes del padrón
- CA-HU13-03: exportable a PDF y Excel (RF-18)
- CA-HU13-04: totales por condición laboral, área y establecimiento

**Ruta:** `GET /reporte/personal`

---

### HU-14: Reporte de Asistencia (RF-16)

Genera un reporte consolidado por trabajador: jornadas, tardanzas, faltas y porcentaje de cumplimiento.

**Criterios de aceptación:**
- CA-HU14-01: filtro por rango de fechas, área y trabajador
- CA-HU14-02: distingue puntual, tardanza, falta y justificado
- CA-HU14-03: porcentaje de cumplimiento de cada trabajador

**Ruta:** `GET /reporte/asistencia`

---

### HU-15: Reporte de Movimientos (RF-17)

Genera un reporte de movimientos institucionales con filtros por tipo, estado, área y periodo.

**Criterios de aceptación:**
- CA-HU15-01: filtros por tipo, personal, área y periodo
- CA-HU15-02: muestra el estado vigente de cada movimiento
- CA-HU15-03: totales consolidados por tipo de movimiento
- CA-HU15-04: exportable a PDF y Excel (RF-18)

**Ruta:** `GET /reporte/movimientos`

---

## Patrón Estrategia de Exportación (RF-18)

```
                    ┌─────────────────────┐
                    │  ExportacionService  │  ← Contexto
                    │  (elige la          │
                    │   estrategia)        │
                    └──────────┬──────────┘
                               │
              ┌────────────────┴────────────────┐
              │                                 │
   ┌──────────▼──────────┐          ┌──────────▼──────────┐
   │   ExportadorPdf     │          │  ExportadorExcel    │
   │  (barryvdh/dompdf)  │          │  (PhpSpreadsheet)   │
   └─────────────────────┘          └─────────────────────┘
```

Agregar un formato nuevo (CSV, ODS) solo requiere:
1. Implementar la interfaz `Exportador`
2. Registrarla en `ExportacionService::$estrategias`

Ningún controlador ni generador cambia.

---

## Trazabilidad (RNF-10 / RNF-12 / Ley 29733)

Cada generación de reporte queda registrada en la tabla `reporte` con:
- Usuario que lo generó
- Tipo de reporte y filtros aplicados
- Formato de salida (PANTALLA / PDF / EXCEL)
- Fecha y hora de generación

Adicionalmente se deja constancia en `log_auditoria` con acción `GENERAR_REPORTE`.

---

## Restricción de área (CA-HU03-03)

El rol `JEFE_AREA` solo visualiza y reporta sobre el personal de **su propia área**. Los roles `ADMIN_RRHH`, `ADMIN_SISTEMA` y `GERENTE_RED` tienen acceso completo.

---

## Permisos requeridos

| Ruta | Permiso |
|------|---------|
| GET /reporte | REPORTES.LEER |
| GET /reporte/personal | REPORTES.LEER |
| GET /reporte/asistencia | REPORTES.LEER |
| GET /reporte/movimientos | REPORTES.LEER |

---

## Dependencias instaladas

```bash
php composer.phar require barryvdh/laravel-dompdf:^3.1 phpoffice/phpspreadsheet:^5.9
```

---

## Cronograma

| Actividad | Inicio | Fin |
|-----------|--------|-----|
| Diseño del patrón Estrategia | 28/07/2026 | 28/07/2026 |
| Implementación de generadores | 28/07/2026 | 29/07/2026 |
| Controlador y rutas | 29/07/2026 | 29/07/2026 |
| Vistas y exportación PDF/Excel | 30/07/2026 | 31/07/2026 |
| Pruebas y ajustes | 04/08/2026 | 08/08/2026 |
