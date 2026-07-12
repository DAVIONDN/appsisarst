# Plan de Pruebas — Sprint 3: Movimientos Institucionales

**Responsable:** David Sedano Humani  
**Fechas:** 12/07/2026 – 13/07/2026

---

## Casos de prueba HU-08: Registrar movimiento

| ID | Caso | Datos de entrada | Resultado esperado |
|---|---|---|---|
| CP-01 | Registro exitoso de licencia | Personal activo, tipo LICENCIA, fechas válidas, motivo ≥10 chars | Movimiento creado en estado PENDIENTE |
| CP-02 | Personal inactivo | Personal con estado INACTIVO | Error: "X está INACTIVO y no admite movimientos" |
| CP-03 | Fecha fin anterior a inicio | fecha_fin < fecha_inicio | Error: "La fecha de fin no puede ser anterior a la de inicio" |
| CP-04 | Comisión sin destino | Tipo COMISION_SERVICIO, sin establecimiento_destino_id | Error: "El tipo exige indicar el establecimiento de destino" |
| CP-05 | Solapamiento de periodos | Personal con movimiento vigente en el mismo periodo | Error: "El trabajador ya tiene un movimiento vigente" |
| CP-06 | Motivo menor a 10 chars | motivo = "corto" | Error de validación Laravel |

---

## Casos de prueba HU-09: Consultar movimientos

| ID | Caso | Acción | Resultado esperado |
|---|---|---|---|
| CP-07 | Listado sin filtros | GET /movimiento | Todos los movimientos paginados de más reciente a más antiguo |
| CP-08 | Filtro por estado | estado=PENDIENTE | Solo movimientos PENDIENTES |
| CP-09 | Filtro por personal | personal_id=N | Solo movimientos del personal N |
| CP-10 | Filtro por periodo | desde=2026-07-01&hasta=2026-07-31 | Solo movimientos que cruzan julio |
| CP-11 | JEFE_AREA ve solo su área | Usuario con rol JEFE_AREA | Solo personal de su área aparece |
| CP-12 | Detalle de movimiento | GET /movimiento/{id} | Ficha completa + historial de auditoría |

---

## Casos de prueba HU-10: Actualizar estado

| ID | Caso | Acción | Resultado esperado |
|---|---|---|---|
| CP-13 | Aprobar movimiento PENDIENTE | PATCH estado=APROBADO | Estado cambia a APROBADO, log registrado |
| CP-14 | Rechazar sin motivo | PATCH estado=RECHAZADO, motivo_rechazo vacío | Error: "debe indicar el motivo del rechazo" |
| CP-15 | Rechazar con motivo | PATCH estado=RECHAZADO, motivo válido | Estado RECHAZADO, motivo guardado |
| CP-16 | Transición ilegal RECHAZADO→APROBADO | PATCH estado=APROBADO en movimiento RECHAZADO | Error: "ya cerró su ciclo y no admite más cambios" |
| CP-17 | Aprobar rotación sin área destino | PATCH estado=APROBADO en ROTACION, sin area_destino_id | Error: "debe indicar el área de destino" |
| CP-18 | Aprobar rotación con área destino | PATCH estado=APROBADO, area_destino_id válido | Estado APROBADO, area_id del personal actualizado |
| CP-19 | Finalizar movimiento vencido | PATCH estado=FINALIZADO en APROBADO vencido | Estado FINALIZADO, log registrado |
| CP-20 | Finalizar vencidos en bloque | PATCH /movimiento/finalizar-vencidos | Todos los APROBADOS vencidos pasan a FINALIZADO |

---

## Resultados de ejecución

| Caso | Estado | Observación |
|---|---|---|
| CP-01 a CP-20 | ✅ APROBADO | Todos los casos ejecutados satisfactoriamente |

---

## Defectos encontrados

Ningún defecto bloqueante. El módulo está listo para Sprint Review.
