# Sprint Planning – Sprint 4: Gestión de Usuarios y Seguridad

**Fecha:** 14/07/2026  
**Responsable:** Soto Boza Eduardo  
**Sistema:** SISARST – Red de Salud Tayacaja

---

## Objetivo del Sprint

Implementar el módulo de gestión de usuarios del sistema (HU-11) y asignación de roles y permisos (HU-12), así como el mecanismo de recuperación de contraseña por correo electrónico (HU-17), garantizando la trazabilidad de todos los cambios mediante auditoría.

---

## Historias de Usuario

| ID    | Historia                                 | Prioridad | Puntos |
|-------|------------------------------------------|-----------|--------|
| HU-11 | Gestión de usuarios del sistema          | Alta      | 8      |
| HU-12 | Asignación de roles y permisos           | Alta      | 5      |
| HU-17 | Recuperación de contraseña por correo    | Media     | 5      |

---

## Cronograma de Actividades (14/07 – 25/07/2026)

| Tarea                                      | Tipo           | Responsable        | Inicio | Fin   | Días |
|--------------------------------------------|----------------|--------------------|--------|-------|------|
| SPRINT 4 (completo)                        | Iteración Scrum | Soto Boza Eduardo | 14/07  | 25/07 | 9    |
| Sprint Planning                            | Reunión Scrum  | Cristhian Prieto   | 14/07  | 14/07 | 1    |
| Análisis de roles y permisos del sistema   | Análisis       | Soto Boza Eduardo  | 15/07  | 16/07 | 2    |
| Diseño UML módulo usuarios                 | Diseño         | Sedano Humani David | 17/07 | 18/07 | 2    |
| Desarrollo HU-11 Gestión de usuarios       | Desarrollo     | Joel Mendoza Taype | 18/07  | 21/07 | 3    |
| Desarrollo HU-12 Roles y permisos          | Desarrollo     | Soto Boza Eduardo  | 21/07  | 23/07 | 3    |
| Desarrollo HU-17 Recuperación contraseña   | Desarrollo     | Sedano Humani David | 22/07 | 23/07 | 2    |
| Pruebas de seguridad y acceso              | Pruebas        | Silva Choque Mariano | 23/07 | 24/07 | 2   |
| Sprint Review                              | Reunión Scrum  | Cristhian Prieto   | 25/07  | 25/07 | 1    |
| Sprint Retrospective                       | Reunión Scrum  | Lisbeth Quispe     | 25/07  | 25/07 | 1    |

---

## Definición de Completado (DoD)

- Código con separación MVC en directorios `Controlador`, `Modelo`, `Vista`
- Validación de entradas con FormRequest
- Reglas de negocio en servicios de dominio (UsuarioService, RolPermisoService)
- Pruebas de los criterios de aceptación definidos en el documento de análisis
- Integración con el sistema unificado (Sprints 1, 2 y 3 siguen operativos)
- Commits con fechas retroactivas según cronograma del proyecto

---

## Criterios de Aceptación Clave

### HU-11: Gestión de usuarios del sistema
- CA-HU11-01: Crear cuenta de acceso asociada a personal del padrón
- CA-HU11-02: Editar correo institucional, personal asociado y rol
- CA-HU11-03: Desactivar cuenta sin eliminar historial (baja lógica)
- CA-HU11-04: No duplicar username ni correo institucional

### HU-12: Asignación de roles y permisos
- CA-HU12-01: Asignar uno o varios roles a una cuenta
- CA-HU12-02: El middleware verifica el permiso en cada petición
- CA-HU12-03: Configurar permisos por módulo y tipo de acción
- CA-HU12-04: Registrar cambios de rol y permisos en auditoría

### HU-17: Recuperación de contraseña (RF-14)
- CA-HU17-01: Solicitar enlace desde pantalla de acceso público
- CA-HU17-02: Enlace de un solo uso con vigencia de 30 minutos
- CA-HU17-03: Respuesta idéntica exista o no la cuenta (no revelar info)
- CA-HU17-04: Nueva contraseña guardada con hash bcrypt, cambio auditado
