# Sprint 4 – Gestión de Usuarios y Seguridad

**SISARST** · Sistema Web Integrado de Gestión del Personal  
**Red de Salud Tayacaja**

---

## Descripción

Sprint 4 implementa el módulo de **Gestión de Usuarios y Seguridad**, que incluye:

- **HU-11**: Creación, edición y desactivación de cuentas de usuario del sistema
- **HU-12**: Asignación y configuración de roles y permisos por módulo
- **HU-17**: Recuperación de contraseña mediante enlace de un solo uso por correo

---

## Historias de Usuario

### HU-11: Gestión de Usuarios del Sistema
Permite al administrador crear cuentas de acceso asociadas al personal del padrón, editar sus datos y gestionar su estado (activo/inactivo/bloqueado).

**Rutas:**
| Método | URL                        | Acción               | Permiso           |
|--------|----------------------------|----------------------|-------------------|
| GET    | /usuario                   | Listar cuentas       | USUARIOS.LEER     |
| GET    | /usuario/nuevo             | Formulario alta      | USUARIOS.ESCRIBIR |
| POST   | /usuario                   | Crear cuenta         | USUARIOS.ESCRIBIR |
| GET    | /usuario/{id}              | Ficha de cuenta      | USUARIOS.LEER     |
| GET    | /usuario/{id}/editar       | Formulario edición   | USUARIOS.EDITAR   |
| PUT    | /usuario/{id}              | Guardar edición      | USUARIOS.EDITAR   |
| PATCH  | /usuario/{id}/baja         | Desactivar cuenta    | USUARIOS.EDITAR   |
| PATCH  | /usuario/{id}/alta         | Reactivar cuenta     | USUARIOS.EDITAR   |
| PATCH  | /usuario/{id}/desbloquear  | Desbloquear cuenta   | USUARIOS.EDITAR   |
| GET    | /usuario/{id}/clave        | Form. restablecer    | USUARIOS.EDITAR   |
| PATCH  | /usuario/{id}/clave        | Restablecer clave    | USUARIOS.EDITAR   |

### HU-12: Asignación de Roles y Permisos
Permite visualizar la matriz de roles × módulos y configurar los permisos de cada rol.

**Rutas:**
| Método | URL                      | Acción              | Permiso         |
|--------|--------------------------|---------------------|-----------------|
| GET    | /rol                     | Matriz de permisos  | USUARIOS.LEER   |
| GET    | /rol/{id}/permisos       | Configurar rol      | USUARIOS.EDITAR |
| PUT    | /rol/{id}/permisos       | Guardar permisos    | USUARIOS.EDITAR |

### HU-17: Recuperación de Contraseña (RF-14)
Flujo de autoservicio mediante enlace temporal de 30 minutos enviado al correo institucional.

**Rutas (públicas):**
| Método | URL                     | Acción                   |
|--------|-------------------------|--------------------------|
| GET    | /olvide-contrasena      | Formulario de solicitud  |
| POST   | /olvide-contrasena      | Enviar enlace de recuperación |
| GET    | /restablecer/{token}    | Formulario de nueva clave |
| POST   | /restablecer            | Guardar nueva contraseña  |

---

## Arquitectura MVC

```
app/
├── Controlador/
│   ├── UsuarioController.php          # HU-11: CRUD de cuentas
│   ├── RolController.php              # HU-12: configuración de permisos
│   ├── Auth/
│   │   └── RecuperacionController.php # HU-17: recuperación de contraseña
│   └── Validaciones/
│       ├── UsuarioRequest.php          # Validación alta/edición de cuentas
│       ├── ClaveRequest.php            # Validación de contraseña nueva
│       ├── PermisosRolRequest.php      # Validación de permisos de rol
│       └── SolicitarRecuperacionRequest.php
├── Modelo/
│   ├── Usuario.php                    # Modelo de cuenta (ya existía)
│   ├── Rol.php                        # Modelo de rol (ya existía)
│   ├── Permiso.php                    # Modelo de permiso (ya existía)
│   └── Servicios/
│       ├── UsuarioService.php          # Reglas HU-11 y HU-12
│       ├── RolPermisoService.php       # Reglas CA-HU12-03
│       └── RecuperacionService.php     # Reglas HU-17 (CA-HU17-01..04)
└── Vista/
    ├── usuario/                        # Vistas HU-11
    │   ├── index.blade.php
    │   ├── show.blade.php
    │   ├── create.blade.php
    │   ├── edit.blade.php
    │   ├── clave.blade.php
    │   └── _formulario.blade.php
    ├── rol/                            # Vistas HU-12
    │   ├── index.blade.php
    │   └── edit.blade.php
    ├── auth/                           # Vistas HU-17
    │   ├── solicitar-recuperacion.blade.php
    │   └── restablecer.blade.php
    └── correos/
        └── recuperacion.blade.php      # Plantilla de correo HU-17
```

---

## Seguridad implementada

- **Contraseñas**: almacenadas con bcrypt (RNF-02). Nunca en texto claro.
- **Token de recuperación**: se guarda el **hash SHA-256** del token, no el token en sí (misma filosofía que la contraseña).
- **Enlace de un solo uso**: el token se invalida al usarse o al vencer (30 minutos).
- **Respuesta genérica**: el sistema nunca revela si un correo está registrado (CA-HU17-03).
- **Bloqueo automático**: tras 3 intentos fallidos de login, la cuenta queda BLOQUEADA (DS-RF13).
- **Auditoría**: todo cambio de cuenta, rol o contraseña queda registrado en `log_auditoria`.
- **ADMIN_SISTEMA protegido**: no se puede desactivar si es el único administrador activo.

---

## Integración con el sistema

El Sprint 4 se integra al sistema unificado **appsisarst** manteniendo operativos todos los sprints anteriores:

| Sprint | Módulo                       | Estado     |
|--------|------------------------------|------------|
| S1     | Padrón de Personal           | ✅ Operativo |
| S2     | Control de Asistencia        | ✅ Operativo |
| S3     | Movimientos Institucionales  | ✅ Operativo |
| S4     | Usuarios y Seguridad         | ✅ Operativo |
| S5     | Reportes Administrativos     | ⏳ Pendiente |

---

## Cronograma ejecutado

| Tarea                                     | Inicio | Fin   |
|-------------------------------------------|--------|-------|
| Sprint Planning                           | 14/07  | 14/07 |
| Análisis de roles y permisos              | 15/07  | 16/07 |
| Diseño UML módulo usuarios                | 17/07  | 18/07 |
| Desarrollo HU-11 Gestión de usuarios      | 18/07  | 21/07 |
| Desarrollo HU-12 Roles y permisos         | 21/07  | 23/07 |
| Desarrollo HU-17 Recuperación contraseña  | 22/07  | 23/07 |
| Pruebas de seguridad y acceso             | 23/07  | 24/07 |
| Sprint Review + Retrospective             | 25/07  | 25/07 |
