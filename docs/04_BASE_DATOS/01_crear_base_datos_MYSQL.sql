-- =============================================================================
--  SISTEMA WEB INTEGRADO PARA LA GESTIÓN DEL PERSONAL
--  Red de Salud Tayacaja
--  ---------------------------------------------------------------------------
--  Script de creación de la base de datos — dialecto MySQL 8.0 / MariaDB 10.11
--  Pensado para MySQL Workbench:
--     File > Import > Reverse Engineer MySQL Create Script
--  Derivado del Diagrama de Clases Único y del Modelo Entidad-Relación
--  del documento de Diseño (versión 2.0 final). Normalizado hasta 3FN.
-- =============================================================================

DROP DATABASE IF EXISTS red_salud_tayacaja;
CREATE DATABASE red_salud_tayacaja
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;
USE red_salud_tayacaja;

-- -----------------------------------------------------------------------------
-- Tabla: establecimiento
-- -----------------------------------------------------------------------------
CREATE TABLE establecimiento (
    establecimiento_id  INT           NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(15)   NOT NULL,
    nombre              VARCHAR(150)  NOT NULL,
    tipo                VARCHAR(50)   NOT NULL,
    microrred           VARCHAR(100)  NOT NULL,
    direccion           VARCHAR(200)  NULL,
    activo              BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (establecimiento_id),
    UNIQUE INDEX uq_establecimiento_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Establecimientos de salud agrupados por microrred';

-- -----------------------------------------------------------------------------
-- Tabla: area
-- -----------------------------------------------------------------------------
CREATE TABLE area (
    area_id             INT           NOT NULL AUTO_INCREMENT,
    establecimiento_id  INT           NOT NULL,
    codigo              VARCHAR(15)   NOT NULL,
    nombre              VARCHAR(120)  NOT NULL,
    descripcion         VARCHAR(255)  NULL,
    activo              BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (area_id),
    UNIQUE INDEX uq_area_codigo (codigo),
    INDEX idx_area_establecimiento (establecimiento_id),
    CONSTRAINT fk_area_establecimiento
        FOREIGN KEY (establecimiento_id)
        REFERENCES establecimiento (establecimiento_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Áreas de trabajo dentro de cada establecimiento';

-- -----------------------------------------------------------------------------
-- Tabla: horario
-- -----------------------------------------------------------------------------
CREATE TABLE horario (
    horario_id      INT          NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(80)  NOT NULL,
    hora_entrada    TIME         NOT NULL,
    hora_salida     TIME         NOT NULL,
    tolerancia_min  SMALLINT     NOT NULL DEFAULT 10,
    dias_laborales  VARCHAR(30)  NOT NULL DEFAULT 'LUN-VIE',
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (horario_id),
    UNIQUE INDEX uq_horario_nombre (nombre),
    CONSTRAINT ck_horario_rango      CHECK (hora_salida > hora_entrada),
    CONSTRAINT ck_horario_tolerancia CHECK (tolerancia_min BETWEEN 0 AND 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Catálogo de horarios de trabajo (RF-19 / HU-16)';

-- -----------------------------------------------------------------------------
-- Tabla: personal
-- -----------------------------------------------------------------------------
CREATE TABLE personal (
    personal_id        INT                        NOT NULL AUTO_INCREMENT,
    area_id            INT                        NOT NULL,
    horario_id         INT                        NULL,
    dni                CHAR(8)                    NOT NULL,
    nombres            VARCHAR(100)               NOT NULL,
    apellidos          VARCHAR(100)               NOT NULL,
    cargo              VARCHAR(80)                NOT NULL,
    condicion_laboral  VARCHAR(50)                NOT NULL,
    telefono           VARCHAR(15)                NULL,
    correo             VARCHAR(120)               NULL,
    fecha_ingreso      DATE                       NOT NULL,
    estado             ENUM('ACTIVO','INACTIVO')  NOT NULL DEFAULT 'ACTIVO',
    motivo_baja        VARCHAR(255)               NULL,
    created_at         TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (personal_id),
    UNIQUE INDEX uq_personal_dni (dni),
    INDEX idx_personal_area (area_id),
    INDEX idx_personal_horario (horario_id),
    INDEX idx_personal_estado (estado),
    INDEX idx_personal_apellidos (apellidos, nombres),
    INDEX idx_personal_condicion (condicion_laboral),
    CONSTRAINT fk_personal_area
        FOREIGN KEY (area_id) REFERENCES area (area_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_personal_horario
        FOREIGN KEY (horario_id) REFERENCES horario (horario_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT ck_personal_dni CHECK (dni REGEXP '^[0-9]{8}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Padrón único del personal de la Red de Salud (RF-01 a RF-04)';

-- -----------------------------------------------------------------------------
-- Tabla: usuario
-- -----------------------------------------------------------------------------
CREATE TABLE usuario (
    usuario_id           INT                                    NOT NULL AUTO_INCREMENT,
    personal_id          INT                                    NOT NULL,
    username             VARCHAR(40)                            NOT NULL,
    password_hash        VARCHAR(255)                           NOT NULL,
    correo_institucional VARCHAR(120)                           NOT NULL,
    intentos_fallidos    SMALLINT                               NOT NULL DEFAULT 0,
    token_recuperacion   VARCHAR(255)                           NULL,
    token_expira         DATETIME                               NULL,
    ultimo_acceso        DATETIME                               NULL,
    estado               ENUM('ACTIVO','INACTIVO','BLOQUEADO')  NOT NULL DEFAULT 'ACTIVO',
    created_at           TIMESTAMP                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP                              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id),
    UNIQUE INDEX uq_usuario_personal (personal_id),
    UNIQUE INDEX uq_usuario_username (username),
    UNIQUE INDEX uq_usuario_correo (correo_institucional),
    INDEX idx_usuario_estado (estado),
    CONSTRAINT fk_usuario_personal
        FOREIGN KEY (personal_id) REFERENCES personal (personal_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_usuario_intentos CHECK (intentos_fallidos BETWEEN 0 AND 10),
    CONSTRAINT ck_usuario_username CHECK (CHAR_LENGTH(username) >= 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Cuentas de acceso al sistema (RF-11, RF-13, RF-14)';

-- -----------------------------------------------------------------------------
-- Tabla: rol
-- -----------------------------------------------------------------------------
CREATE TABLE rol (
    rol_id      INT           NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(50)   NOT NULL,
    descripcion VARCHAR(255)  NULL,
    activo      BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rol_id),
    UNIQUE INDEX uq_rol_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Perfiles funcionales que agrupan permisos (RF-12)';

-- -----------------------------------------------------------------------------
-- Tabla: permiso
-- -----------------------------------------------------------------------------
CREATE TABLE permiso (
    permiso_id  INT                                            NOT NULL AUTO_INCREMENT,
    modulo      VARCHAR(50)                                    NOT NULL,
    accion      ENUM('LEER','ESCRIBIR','EDITAR','ELIMINAR')    NOT NULL,
    descripcion VARCHAR(255)                                   NULL,
    created_at  TIMESTAMP                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (permiso_id),
    UNIQUE INDEX uq_permiso_modulo_accion (modulo, accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Permisos por módulo y tipo de acción (CA-HU12-03)';

-- -----------------------------------------------------------------------------
-- Tabla: usuario_rol  (resuelve la relación N:M usuario–rol)
-- -----------------------------------------------------------------------------
CREATE TABLE usuario_rol (
    usuario_id       INT       NOT NULL,
    rol_id           INT       NOT NULL,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    asignado_por     INT       NULL,
    PRIMARY KEY (usuario_id, rol_id),
    INDEX idx_usuario_rol_rol (rol_id),
    INDEX idx_usuario_rol_asignador (asignado_por),
    CONSTRAINT fk_usuario_rol_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario (usuario_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_usuario_rol_rol
        FOREIGN KEY (rol_id) REFERENCES rol (rol_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_rol_asignador
        FOREIGN KEY (asignado_por) REFERENCES usuario (usuario_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Tabla intermedia de la relación N:M entre usuario y rol';

-- -----------------------------------------------------------------------------
-- Tabla: rol_permiso  (resuelve la relación N:M rol–permiso)
-- -----------------------------------------------------------------------------
CREATE TABLE rol_permiso (
    rol_id     INT       NOT NULL,
    permiso_id INT       NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (rol_id, permiso_id),
    INDEX idx_rol_permiso_permiso (permiso_id),
    CONSTRAINT fk_rol_permiso_rol
        FOREIGN KEY (rol_id) REFERENCES rol (rol_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rol_permiso_permiso
        FOREIGN KEY (permiso_id) REFERENCES permiso (permiso_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Tabla intermedia de la relación N:M entre rol y permiso';

-- -----------------------------------------------------------------------------
-- Tabla: asistencia
-- -----------------------------------------------------------------------------
CREATE TABLE asistencia (
    asistencia_id    INT                                                  NOT NULL AUTO_INCREMENT,
    personal_id      INT                                                  NOT NULL,
    fecha            DATE                                                 NOT NULL,
    hora_entrada     TIME                                                 NULL,
    hora_salida      TIME                                                 NULL,
    estado           ENUM('PUNTUAL','TARDANZA','FALTA','JUSTIFICADO')     NOT NULL,
    minutos_tardanza SMALLINT                                             NOT NULL DEFAULT 0,
    origen           ENUM('MANUAL','AUTOMATICO')                          NOT NULL DEFAULT 'MANUAL',
    observacion      VARCHAR(255)                                         NULL,
    created_at       TIMESTAMP                                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP                                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (asistencia_id),
    UNIQUE INDEX uq_asistencia_personal_fecha (personal_id, fecha),
    INDEX idx_asistencia_fecha (fecha),
    INDEX idx_asistencia_estado (estado),
    CONSTRAINT fk_asistencia_personal
        FOREIGN KEY (personal_id) REFERENCES personal (personal_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_asistencia_salida   CHECK (hora_salida IS NULL OR hora_entrada IS NULL OR hora_salida >= hora_entrada),
    CONSTRAINT ck_asistencia_tardanza CHECK (minutos_tardanza >= 0),
    CONSTRAINT ck_asistencia_coherencia CHECK (
        (estado = 'TARDANZA' AND minutos_tardanza > 0 AND hora_entrada IS NOT NULL) OR
        (estado = 'PUNTUAL'  AND minutos_tardanza = 0 AND hora_entrada IS NOT NULL) OR
        (estado IN ('FALTA','JUSTIFICADO') AND minutos_tardanza = 0 AND hora_entrada IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Registro diario de asistencia (RF-05 a RF-07)';

-- -----------------------------------------------------------------------------
-- Tabla: tipo_movimiento
-- -----------------------------------------------------------------------------
CREATE TABLE tipo_movimiento (
    tipo_movimiento_id INT           NOT NULL AUTO_INCREMENT,
    nombre             VARCHAR(60)   NOT NULL,
    descripcion        VARCHAR(255)  NULL,
    requiere_destino   BOOLEAN       NOT NULL DEFAULT FALSE,
    activo             BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tipo_movimiento_id),
    UNIQUE INDEX uq_tipo_movimiento_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Catálogo de tipos de movimiento institucional';

-- -----------------------------------------------------------------------------
-- Tabla: movimiento_institucional
-- -----------------------------------------------------------------------------
CREATE TABLE movimiento_institucional (
    movimiento_id              INT                                                        NOT NULL AUTO_INCREMENT,
    personal_id                INT                                                        NOT NULL,
    tipo_movimiento_id         INT                                                        NOT NULL,
    establecimiento_destino_id INT                                                        NULL,
    fecha_inicio               DATE                                                       NOT NULL,
    fecha_fin                  DATE                                                       NOT NULL,
    motivo                     VARCHAR(255)                                               NOT NULL,
    estado                     ENUM('PENDIENTE','APROBADO','RECHAZADO','FINALIZADO')      NOT NULL DEFAULT 'PENDIENTE',
    motivo_rechazo             VARCHAR(255)                                               NULL,
    created_at                 TIMESTAMP                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (movimiento_id),
    INDEX idx_movimiento_personal (personal_id),
    INDEX idx_movimiento_tipo (tipo_movimiento_id),
    INDEX idx_movimiento_destino (establecimiento_destino_id),
    INDEX idx_movimiento_estado (estado),
    INDEX idx_movimiento_rango (fecha_inicio, fecha_fin),
    CONSTRAINT fk_movimiento_personal
        FOREIGN KEY (personal_id) REFERENCES personal (personal_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_tipo
        FOREIGN KEY (tipo_movimiento_id) REFERENCES tipo_movimiento (tipo_movimiento_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_destino
        FOREIGN KEY (establecimiento_destino_id) REFERENCES establecimiento (establecimiento_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_movimiento_fechas  CHECK (fecha_fin >= fecha_inicio),
    CONSTRAINT ck_movimiento_rechazo CHECK (estado <> 'RECHAZADO' OR motivo_rechazo IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Comisiones, rotaciones, licencias y permisos (RF-08 a RF-10)';

-- -----------------------------------------------------------------------------
-- Tabla: log_auditoria
-- -----------------------------------------------------------------------------
CREATE TABLE log_auditoria (
    log_id      BIGINT        NOT NULL AUTO_INCREMENT,
    usuario_id  INT           NULL,
    fecha       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    entidad     VARCHAR(60)   NOT NULL,
    registro_id INT           NULL,
    accion      VARCHAR(40)   NOT NULL,
    detalle     TEXT          NULL,
    ip_origen   VARCHAR(45)   NULL,
    PRIMARY KEY (log_id),
    INDEX idx_log_usuario (usuario_id),
    INDEX idx_log_fecha (fecha),
    INDEX idx_log_entidad (entidad, registro_id),
    CONSTRAINT fk_log_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario (usuario_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Bitácora de acciones de escritura del sistema (RNF-10)';

-- -----------------------------------------------------------------------------
-- Tabla: reporte
-- -----------------------------------------------------------------------------
CREATE TABLE reporte (
    reporte_id       INT                                              NOT NULL AUTO_INCREMENT,
    usuario_id       INT                                              NOT NULL,
    tipo             ENUM('PERSONAL','ASISTENCIA','MOVIMIENTOS')      NOT NULL,
    parametros       JSON                                             NULL,
    formato          ENUM('PANTALLA','PDF','EXCEL')                   NULL,
    fecha_generacion DATETIME                                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reporte_id),
    INDEX idx_reporte_usuario (usuario_id),
    CONSTRAINT fk_reporte_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario (usuario_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Reportes generados con los filtros aplicados (RF-15 a RF-18)';

-- =============================================================================
--  VISTAS DE APOYO A LOS REPORTES
-- =============================================================================

CREATE OR REPLACE VIEW v_padron_personal AS
SELECT p.personal_id,
       p.dni,
       CONCAT(p.apellidos, ', ', p.nombres) AS nombre_completo,
       p.cargo,
       p.condicion_laboral,
       a.nombre  AS area,
       e.nombre  AS establecimiento,
       e.microrred,
       h.nombre  AS horario,
       p.fecha_ingreso,
       p.estado
FROM personal p
JOIN area a            ON a.area_id = p.area_id
JOIN establecimiento e ON e.establecimiento_id = a.establecimiento_id
LEFT JOIN horario h    ON h.horario_id = p.horario_id;

CREATE OR REPLACE VIEW v_resumen_asistencia AS
SELECT p.personal_id,
       p.dni,
       CONCAT(p.apellidos, ', ', p.nombres) AS nombre_completo,
       a.nombre AS area,
       SUM(asi.estado = 'PUNTUAL')     AS puntuales,
       SUM(asi.estado = 'TARDANZA')    AS tardanzas,
       SUM(asi.estado = 'FALTA')       AS faltas,
       SUM(asi.estado = 'JUSTIFICADO') AS justificados,
       COALESCE(SUM(asi.minutos_tardanza), 0) AS total_minutos_tardanza,
       ROUND(100.0 * SUM(asi.estado IN ('PUNTUAL','JUSTIFICADO'))
             / NULLIF(COUNT(asi.asistencia_id), 0), 2) AS porcentaje_cumplimiento
FROM personal p
JOIN area a              ON a.area_id = p.area_id
LEFT JOIN asistencia asi ON asi.personal_id = p.personal_id
GROUP BY p.personal_id, p.dni, p.apellidos, p.nombres, a.nombre;

CREATE OR REPLACE VIEW v_movimientos_detalle AS
SELECT m.movimiento_id,
       p.dni,
       CONCAT(p.apellidos, ', ', p.nombres) AS nombre_completo,
       a.nombre  AS area_origen,
       tm.nombre AS tipo_movimiento,
       ed.nombre AS establecimiento_destino,
       m.fecha_inicio,
       m.fecha_fin,
       (DATEDIFF(m.fecha_fin, m.fecha_inicio) + 1) AS dias,
       m.motivo,
       m.estado
FROM movimiento_institucional m
JOIN personal p              ON p.personal_id = m.personal_id
JOIN area a                  ON a.area_id = p.area_id
JOIN tipo_movimiento tm      ON tm.tipo_movimiento_id = m.tipo_movimiento_id
LEFT JOIN establecimiento ed ON ed.establecimiento_id = m.establecimiento_destino_id;

-- =============================================================================
--  DISPARADORES
--  Reglas que no pueden expresarse como CHECK porque consultan otras filas.
-- =============================================================================
DELIMITER $$

-- La fecha de ingreso no puede ser futura. En MySQL esta regla no puede
-- expresarse como CHECK porque CURRENT_DATE es una función no determinista,
-- de modo que se implementa con disparadores de alta y modificación.
CREATE TRIGGER tg_personal_ingreso_ins
BEFORE INSERT ON personal
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso > CURRENT_DATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser futura';
    END IF;
END$$

CREATE TRIGGER tg_personal_ingreso_upd
BEFORE UPDATE ON personal
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso > CURRENT_DATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser futura';
    END IF;
END$$

-- Impide que un trabajador tenga dos movimientos vigentes que se crucen
-- en el tiempo (validación verificarSolapamiento del diagrama DS-HU08).
CREATE TRIGGER tg_movimiento_solapa_ins
BEFORE INSERT ON movimiento_institucional
FOR EACH ROW
BEGIN
    DECLARE v_conflictos INT;
    SELECT COUNT(*) INTO v_conflictos
    FROM movimiento_institucional m
    WHERE m.personal_id = NEW.personal_id
      AND m.estado IN ('PENDIENTE','APROBADO')
      AND NEW.fecha_inicio <= m.fecha_fin
      AND NEW.fecha_fin    >= m.fecha_inicio;
    IF v_conflictos > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El personal ya tiene un movimiento vigente en ese periodo';
    END IF;
END$$

-- La misma regla debe aplicarse al modificar un movimiento existente, porque
-- cambiar sus fechas o su titular puede provocar el cruce que el alta evitó.
-- Se excluye la propia fila de la comparación.
CREATE TRIGGER tg_movimiento_solapa_upd
BEFORE UPDATE ON movimiento_institucional
FOR EACH ROW
BEGIN
    DECLARE v_conflictos INT;
    IF NEW.estado IN ('PENDIENTE','APROBADO') THEN
        SELECT COUNT(*) INTO v_conflictos
        FROM movimiento_institucional m
        WHERE m.personal_id   = NEW.personal_id
          AND m.movimiento_id <> NEW.movimiento_id
          AND m.estado IN ('PENDIENTE','APROBADO')
          AND NEW.fecha_inicio <= m.fecha_fin
          AND NEW.fecha_fin    >= m.fecha_inicio;
        IF v_conflictos > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El personal ya tiene un movimiento vigente en ese periodo';
        END IF;
    END IF;
END$$

-- Valida la máquina de estados: PENDIENTE va a APROBADO o RECHAZADO,
-- y APROBADO solo puede pasar a FINALIZADO.
CREATE TRIGGER tg_movimiento_transicion
BEFORE UPDATE ON movimiento_institucional
FOR EACH ROW
BEGIN
    IF NEW.estado <> OLD.estado THEN
        IF NOT ((OLD.estado = 'PENDIENTE' AND NEW.estado IN ('APROBADO','RECHAZADO'))
             OR (OLD.estado = 'APROBADO'  AND NEW.estado = 'FINALIZADO')) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transicion de estado no permitida para el movimiento';
        END IF;
    END IF;
END$$

DELIMITER ;

-- =============================================================================
--  DATOS INICIALES (catálogos maestros)
-- =============================================================================

INSERT INTO rol (nombre, descripcion) VALUES
 ('ADMIN_SISTEMA', 'Administra usuarios, roles y permisos del sistema'),
 ('ADMIN_RRHH',    'Gestiona el padrón, la asistencia y los movimientos'),
 ('JEFE_AREA',     'Consulta la información del personal de su área'),
 ('GERENTE_RED',   'Consulta reportes consolidados de toda la red');

INSERT INTO permiso (modulo, accion, descripcion) VALUES
 ('PADRON',      'LEER',     'Consultar el padrón de personal'),
 ('PADRON',      'ESCRIBIR', 'Registrar personal nuevo'),
 ('PADRON',      'EDITAR',   'Modificar datos del personal'),
 ('PADRON',      'ELIMINAR', 'Desactivar personal'),
 ('ASISTENCIA',  'LEER',     'Consultar registros de asistencia'),
 ('ASISTENCIA',  'ESCRIBIR', 'Registrar entradas y salidas'),
 ('MOVIMIENTOS', 'LEER',     'Consultar movimientos institucionales'),
 ('MOVIMIENTOS', 'ESCRIBIR', 'Registrar movimientos institucionales'),
 ('MOVIMIENTOS', 'EDITAR',   'Actualizar el estado de los movimientos'),
 ('USUARIOS',    'LEER',     'Consultar cuentas de usuario'),
 ('USUARIOS',    'ESCRIBIR', 'Crear cuentas de usuario'),
 ('USUARIOS',    'EDITAR',   'Editar o desactivar cuentas'),
 ('REPORTES',    'LEER',     'Generar y exportar reportes');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id FROM rol r, permiso p WHERE r.nombre = 'ADMIN_SISTEMA';

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id FROM rol r, permiso p
WHERE r.nombre = 'ADMIN_RRHH' AND p.modulo IN ('PADRON','ASISTENCIA','MOVIMIENTOS','REPORTES');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id FROM rol r, permiso p
WHERE r.nombre = 'JEFE_AREA' AND p.accion = 'LEER'
  AND p.modulo IN ('PADRON','ASISTENCIA','MOVIMIENTOS','REPORTES');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id FROM rol r, permiso p
WHERE r.nombre = 'GERENTE_RED' AND p.accion = 'LEER'
  AND p.modulo IN ('PADRON','MOVIMIENTOS','REPORTES');

INSERT INTO tipo_movimiento (nombre, descripcion, requiere_destino) VALUES
 ('COMISION_SERVICIO', 'Desplazamiento temporal a otro establecimiento',        TRUE),
 ('ROTACION',          'Cambio permanente de establecimiento o área',           TRUE),
 ('LICENCIA',          'Ausencia autorizada por motivos personales o de salud', FALSE),
 ('PERMISO',           'Ausencia breve autorizada por el jefe inmediato',       FALSE),
 ('VACACIONES',        'Descanso físico anual programado',                      FALSE);

INSERT INTO horario (nombre, hora_entrada, hora_salida, tolerancia_min, dias_laborales) VALUES
 ('Administrativo',        '08:00:00', '16:00:00', 10, 'LUN-VIE'),
 ('Asistencial diurno',    '07:00:00', '19:00:00', 10, 'LUN-DOM'),
 ('Asistencial nocturno',  '19:00:00', '23:59:00', 15, 'LUN-DOM'),
 ('Medio tiempo',          '08:00:00', '12:00:00', 10, 'LUN-VIE');
