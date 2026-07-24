# Pruebas de Validación – Sprint 4: Gestión de Usuarios y Seguridad

**Fecha de ejecución:** 23/07/2026 – 24/07/2026  
**Responsable:** Silva Choque Mariano  
**Sistema:** SISARST – Red de Salud Tayacaja

---

## HU-11: Gestión de Usuarios del Sistema

### CP-01: Alta de cuenta de usuario (CA-HU11-01)
- **Precondición:** Personal activo en el padrón sin cuenta
- **Entrada:** personal_id=3, username="rhuaman", password="Salud2026", correo=rhuaman@redsaludtayacaja.gob.pe, roles=[JEFE_AREA]
- **Acción:** POST /usuario
- **Resultado esperado:** Cuenta creada, redirige a ficha con mensaje "Cuenta rhuaman creada"
- **Estado:** ✅ PASA

### CP-02: Nombre de usuario duplicado (CA-HU11-04)
- **Precondición:** Existe cuenta con username="mquispe"
- **Entrada:** username="mquispe"
- **Resultado esperado:** Error de validación "Ese nombre de usuario ya está en uso"
- **Estado:** ✅ PASA

### CP-03: Correo institucional duplicado (CA-HU11-04)
- **Precondición:** Existe cuenta con correo mquispe@redsaludtayacaja.gob.pe
- **Entrada:** correo_institucional="mquispe@redsaludtayacaja.gob.pe" en nueva cuenta
- **Resultado esperado:** Error de validación "Ese correo institucional ya está registrado"
- **Estado:** ✅ PASA

### CP-04: Contraseña débil (RNF-02)
- **Entrada:** password="12345" (sin letras)
- **Resultado esperado:** Error de validación de política de contraseña
- **Estado:** ✅ PASA

### CP-05: Personal ya tiene cuenta
- **Entrada:** personal_id de un trabajador que ya tiene cuenta
- **Resultado esperado:** Error "Ese trabajador ya tiene una cuenta de acceso"
- **Estado:** ✅ PASA

### CP-06: Editar correo institucional (CA-HU11-02)
- **Acción:** PUT /usuario/{id} con nuevo correo
- **Resultado esperado:** Correo actualizado, cambio registrado en auditoría
- **Estado:** ✅ PASA

### CP-07: Desactivar cuenta (CA-HU11-03)
- **Precondición:** Cuenta activa, no es la única ADMIN_SISTEMA
- **Acción:** PATCH /usuario/{id}/baja
- **Resultado esperado:** Estado → INACTIVO, historial conservado, cuenta no elimina registros previos
- **Estado:** ✅ PASA

### CP-08: No se puede desactivar el último ADMIN_SISTEMA
- **Precondición:** Solo existe una cuenta con rol ADMIN_SISTEMA activa
- **Acción:** PATCH /usuario/{id}/baja
- **Resultado esperado:** Error "No se puede desactivar la única cuenta con rol ADMIN_SISTEMA"
- **Estado:** ✅ PASA

### CP-09: Reactivar cuenta inactiva
- **Acción:** PATCH /usuario/{id}/alta
- **Resultado esperado:** Estado → ACTIVO, registrado en auditoría
- **Estado:** ✅ PASA

### CP-10: Desbloquear cuenta bloqueada
- **Precondición:** Cuenta con estado=BLOQUEADO (3+ intentos fallidos)
- **Acción:** PATCH /usuario/{id}/desbloquear
- **Resultado esperado:** Estado → ACTIVO, intentos_fallidos=0
- **Estado:** ✅ PASA

---

## HU-12: Asignación de Roles y Permisos

### CP-11: Asignar rol a usuario (CA-HU12-01)
- **Entrada:** roles[]=[2] (ADMIN_RRHH)
- **Resultado esperado:** Rol asignado, registrado en auditoría con "Roles de usuario: [X] -> [Y]"
- **Estado:** ✅ PASA

### CP-12: Cuenta sin rol (CA-HU12-01)
- **Entrada:** roles=[] (vacío)
- **Resultado esperado:** Error "Asigne al menos un rol"
- **Estado:** ✅ PASA

### CP-13: Verificar permiso en petición (CA-HU12-02)
- **Precondición:** Usuario con rol JEFE_AREA (sin permiso USUARIOS.ESCRIBIR)
- **Acción:** GET /usuario/nuevo
- **Resultado esperado:** Redirige con error 403 / "Acceso denegado"
- **Estado:** ✅ PASA

### CP-14: Configurar permisos de un rol (CA-HU12-03)
- **Acción:** PUT /rol/{id}/permisos con selección de módulos/acciones
- **Resultado esperado:** Permisos actualizados, registrado en auditoría CONFIGURAR_PERMISOS
- **Estado:** ✅ PASA

### CP-15: ADMIN_SISTEMA conserva módulo USUARIOS (CA-HU12-03)
- **Precondición:** Rol = ADMIN_SISTEMA
- **Entrada:** Intentar quitar todos los permisos del módulo USUARIOS
- **Resultado esperado:** Error "ADMIN_SISTEMA debe conservar todos los permisos del módulo USUARIOS"
- **Estado:** ✅ PASA

---

## HU-17: Recuperación de Contraseña

### CP-16: Solicitar enlace (CA-HU17-01)
- **Entrada:** correo_institucional="mquispe@redsaludtayacaja.gob.pe"
- **Resultado esperado:** Enlace enviado al correo; respuesta genérica sin revelar si existe (CA-HU17-03)
- **Estado:** ✅ PASA

### CP-17: Correo no registrado (CA-HU17-03)
- **Entrada:** correo_institucional="noexiste@test.com"
- **Resultado esperado:** Mismo mensaje genérico que CP-16 (no revela que no existe)
- **Estado:** ✅ PASA

### CP-18: Enlace vigente (CA-HU17-02)
- **Precondición:** Token generado hace menos de 30 minutos
- **Acción:** GET /restablecer/{token}
- **Resultado esperado:** Formulario de nueva contraseña con tiempo restante
- **Estado:** ✅ PASA

### CP-19: Enlace vencido (CA-HU17-02)
- **Precondición:** Token con token_expira < NOW()
- **Acción:** GET /restablecer/{token}
- **Resultado esperado:** Redirige a solicitar-recuperacion con error "enlace no válido o ya venció"
- **Estado:** ✅ PASA

### CP-20: Restablecer contraseña (CA-HU17-04)
- **Entrada:** password="NuevaClave2026", password_confirmation="NuevaClave2026", token válido
- **Resultado esperado:** Contraseña guardada con hash, token invalidado, auditado como RESTABLECER_CLAVE
- **Estado:** ✅ PASA

---

## Resumen de Pruebas

| Módulo   | Total | Pasan | Fallan |
|----------|-------|-------|--------|
| HU-11    | 10    | 10    | 0      |
| HU-12    | 5     | 5     | 0      |
| HU-17    | 5     | 5     | 0      |
| **Total**| **20**| **20**| **0**  |

Todos los criterios de aceptación del Sprint 4 son satisfechos.
