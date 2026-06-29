-- ============================================================================
--  SISARST - CUENTAS DE ACCESO DE DEMOSTRACION
--  Sprint 1: Padron de Personal
--  ---------------------------------------------------------------------------
--  Ejecutar DESPUES de:
--     01_crear_base_datos_MYSQL.sql
--     02_datos_iniciales_MYSQL.sql
--
--  Motivo de este script
--  ---------------------
--  Los valores password_hash que carga el script 02 son cadenas de ejemplo
--  con prefijo $2b$ que no corresponden a ninguna contrasena real, de modo
--  que ningun usuario podria iniciar sesion. Aqui se reemplazan por hash
--  bcrypt validos generados con PHP (algoritmo $2y$, coste 12), que es el
--  formato que verifica Laravel (RNF-02).
--
--  CONTRASENA DE LAS TRES CUENTAS:  Sisarst2026$
--
--  ATENCION: cambie estas contrasenas antes de cualquier despliegue que no
--  sea el entorno local de pruebas.
-- ============================================================================
USE red_salud_tayacaja;

-- ---------------------------------------------------------------------------
--  1. Hash bcrypt real para las tres cuentas ya creadas por el script 02
-- ---------------------------------------------------------------------------
UPDATE usuario
   SET password_hash     = '$2y$12$uGH6Dm9AgImiIwdUIb461uXXwKzHHPdNhRKTYy7CsJRikGYayk45O',
       intentos_fallidos = 0,
       estado            = 'ACTIVO'
 WHERE username IN ('mquispe', 'cramos', 'rhuaman');

-- ---------------------------------------------------------------------------
--  2. Cuenta adicional para el rol GERENTE_RED
--     El script 02 no crea ninguna cuenta con este perfil y el Sprint 1
--     necesita poder verificar el acceso de solo lectura al padron.
--     Se asocia al trabajador 5 (Paucar Sedano, Ana Lucia).
-- ---------------------------------------------------------------------------
INSERT INTO usuario (personal_id, username, password_hash, correo_institucional, estado)
SELECT 5,
       'apaucar',
       '$2y$12$uGH6Dm9AgImiIwdUIb461uXXwKzHHPdNhRKTYy7CsJRikGYayk45O',
       'apaucar@redsaludtayacaja.gob.pe',
       'ACTIVO'
 WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE username = 'apaucar');

INSERT INTO usuario_rol (usuario_id, rol_id)
SELECT u.usuario_id, r.rol_id
  FROM usuario u
  JOIN rol r ON r.nombre = 'GERENTE_RED'
 WHERE u.username = 'apaucar'
   AND NOT EXISTS (
       SELECT 1 FROM usuario_rol ur
        WHERE ur.usuario_id = u.usuario_id AND ur.rol_id = r.rol_id
   );

-- ---------------------------------------------------------------------------
--  3. Verificacion: cuentas disponibles y su rol
-- ---------------------------------------------------------------------------
SELECT u.usuario_id,
       u.username,
       CONCAT(p.apellidos, ', ', p.nombres) AS trabajador,
       a.nombre  AS area,
       GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles,
       u.estado
  FROM usuario u
  JOIN personal p     ON p.personal_id = u.personal_id
  JOIN area a         ON a.area_id     = p.area_id
  LEFT JOIN usuario_rol ur ON ur.usuario_id = u.usuario_id
  LEFT JOIN rol r          ON r.rol_id      = ur.rol_id
 GROUP BY u.usuario_id, u.username, p.apellidos, p.nombres, a.nombre, u.estado
 ORDER BY u.usuario_id;

-- ============================================================================
--  RESUMEN DE ACCESOS  (contrasena unica: Sisarst2026$)
--  ---------------------------------------------------------------------------
--   mquispe   ADMIN_RRHH      Padron completo: registrar, editar, consultar, dar de baja
--   cramos    ADMIN_SISTEMA   Todos los permisos del sistema
--   rhuaman   JEFE_AREA       Solo consulta, y solo del personal de su area
--   apaucar   GERENTE_RED     Solo consulta del padron y de los reportes
-- ============================================================================
