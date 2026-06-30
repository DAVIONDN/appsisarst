# Sprint Planning — Sprint 3: Movimientos Institucionales

**Fecha:** 30/06/2026  
**Facilitador:** Cristhian Prieto Hinojosa  
**Equipo:** Silva Choque Mariano, Joel Mendoza Taype, Sedano Humani David, Soto Boza Eduardo

---

## Objetivo del Sprint

Implementar el módulo de **Gestión de Movimientos Institucionales** del personal de la Red de Salud Tayacaja, cubriendo el registro, consulta y actualización de estado de comisiones de servicio, rotaciones, licencias, permisos y vacaciones.

---

## Historias de usuario comprometidas

| ID | Historia | Criterios de aceptación clave | Estimación |
|---|---|---|---|
| HU-08 | Registrar movimiento | CA-HU08-01, CA-HU08-02, CA-HU08-03 | 5 pts |
| HU-09 | Consultar movimientos | CA-HU09-01, CA-HU09-02, CA-HU09-03 | 3 pts |
| HU-10 | Actualizar estado | CA-HU10-01, CA-HU10-02 | 5 pts |

**Total:** 13 puntos de historia

---

## Máquina de estados acordada (observación H-09)

```
PENDIENTE → APROBADO → FINALIZADO
PENDIENTE → RECHAZADO
```

- No existen otras transiciones
- El rechazo exige motivo obligatorio
- La rotación aprobada actualiza el padrón automáticamente

---

## Cronograma interno

| Tarea | Inicio | Fin | Responsable |
|---|---|---|---|
| Análisis proceso movimientos | 01/07 | 02/07 | Eduardo Soto Boza |
| Diseño UML módulo movimientos | 03/07 | 05/07 | David Sedano Humani |
| Desarrollo HU-08 | 07/07 | 09/07 | Mariano Silva Choque |
| Desarrollo HU-09 | 09/07 | 11/07 | Eduardo Soto Boza |
| Desarrollo HU-10 | 11/07 | 12/07 | Joel Mendoza Taype |
| Pruebas del módulo | 12/07 | 13/07 | David Sedano Humani |
| Sprint Review | 14/07 | 14/07 | Cristhian Prieto |

---

## Restricciones técnicas

- No usar migraciones de Laravel; el esquema viene de los scripts SQL
- Namespaces: `App\Controlador`, `App\Modelo`, `App\Vista`
- El permiso `MOVIMIENTOS.LEER/ESCRIBIR/EDITAR` debe crearse en la tabla `permiso`
- Respetar la arquitectura MVC del proyecto (igual que Sprints 1 y 2)
