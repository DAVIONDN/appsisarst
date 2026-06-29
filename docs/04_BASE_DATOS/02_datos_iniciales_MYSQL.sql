-- ============================================================================
--  PRUEBAS DE VALIDACIÓN DE LA BASE DE DATOS
--  Carga datos de ejemplo y comprueba que las restricciones funcionen
-- ============================================================================
USE red_salud_tayacaja;

-- ---------- carga de datos de ejemplo ----------
INSERT INTO establecimiento (codigo, nombre, tipo, microrred, direccion) VALUES
 ('EST-001', 'Hospital de Pampas',        'Hospital II-1',   'Pampas',    'Jr. Grau 100, Pampas'),
 ('EST-002', 'Centro de Salud Daniel Hernández', 'Centro de Salud I-3', 'Pampas', 'Av. Los Andes s/n'),
 ('EST-003', 'Puesto de Salud Acraquia',  'Puesto de Salud I-2', 'Acraquia', 'Plaza principal, Acraquia');

INSERT INTO area (establecimiento_id, codigo, nombre, descripcion) VALUES
 (1, 'ARE-001', 'Recursos Humanos', 'Gestión del personal de la red'),
 (1, 'ARE-002', 'Medicina General',  'Atención ambulatoria'),
 (1, 'ARE-003', 'Enfermería',        'Cuidados de enfermería'),
 (2, 'ARE-004', 'Obstetricia',       'Atención materno perinatal'),
 (3, 'ARE-005', 'Atención Primaria', 'Atención básica de salud');

INSERT INTO personal (area_id, horario_id, dni, nombres, apellidos, cargo, condicion_laboral, telefono, correo, fecha_ingreso) VALUES
 (1, 1, '45678912', 'María Elena',  'Quispe Huamán',   'Jefe de Recursos Humanos', 'NOMBRADO',      '964123456', 'mquispe@redsaludtayacaja.gob.pe', '2015-03-02'),
 (1, 1, '41236589', 'Carlos Alberto','Ramos Ccanto',   'Asistente de RRHH',        'CAS',           '965234567', 'cramos@redsaludtayacaja.gob.pe',  '2019-06-17'),
 (2, 2, '43215678', 'Rosa María',   'Huamán Lapa',     'Médico General',           'NOMBRADO',      '966345678', 'rhuaman@redsaludtayacaja.gob.pe', '2012-01-09'),
 (3, 2, '47896321', 'Juan Carlos',  'Torres Mendoza',  'Enfermero',                'CAS',           '967456789', NULL,                              '2021-04-05'),
 (4, 2, '42589631', 'Ana Lucía',    'Paucar Sedano',   'Obstetra',                 'NOMBRADO',      '968567890', 'apaucar@redsaludtayacaja.gob.pe', '2018-08-20'),
 (5, 4, '48521369', 'Pedro Luis',   'Ccente Boza',     'Técnico en Enfermería',    'TERCEROS',      '969678901', NULL,                              '2022-02-14');

INSERT INTO usuario (personal_id, username, password_hash, correo_institucional) VALUES
 (1, 'mquispe', '$2b$12$K8xQvZ3mN7pL2wR5tY9uAeH4jF6sD1gV8cX0bN3mQ7wE5rT2yU9iO', 'mquispe@redsaludtayacaja.gob.pe'),
 (2, 'cramos',  '$2b$12$L9yRwA4nO8qM3xS6uZ0vBfI5kG7tE2hW9dY1cO4nR8xF6sU3zV0jP', 'cramos@redsaludtayacaja.gob.pe'),
 (3, 'rhuaman', '$2b$12$M0zSxB5oP9rN4yT7vA1wCgJ6lH8uF3iX0eZ2dP5oS9yG7tV4aW1kQ', 'rhuaman@redsaludtayacaja.gob.pe');

INSERT INTO usuario_rol (usuario_id, rol_id) VALUES
 (1, (SELECT rol_id FROM rol WHERE nombre = 'ADMIN_RRHH')),
 (2, (SELECT rol_id FROM rol WHERE nombre = 'ADMIN_SISTEMA')),
 (3, (SELECT rol_id FROM rol WHERE nombre = 'JEFE_AREA'));

INSERT INTO asistencia (personal_id, fecha, hora_entrada, hora_salida, estado, minutos_tardanza, origen) VALUES
 (1, '2026-07-27', '07:55', '16:05', 'PUNTUAL',  0,  'MANUAL'),
 (1, '2026-07-28', '08:25', '16:10', 'TARDANZA', 15, 'MANUAL'),
 (2, '2026-07-27', '08:02', '16:00', 'PUNTUAL',  0,  'MANUAL'),
 (2, '2026-07-28', NULL,    NULL,    'FALTA',    0,  'AUTOMATICO'),
 (3, '2026-07-27', '06:58', '19:02', 'PUNTUAL',  0,  'MANUAL'),
 (3, '2026-07-28', NULL,    NULL,    'JUSTIFICADO', 0, 'AUTOMATICO'),
 (4, '2026-07-27', '07:20', '19:00', 'TARDANZA', 10, 'MANUAL');

INSERT INTO movimiento_institucional (personal_id, tipo_movimiento_id, establecimiento_destino_id, fecha_inicio, fecha_fin, motivo) VALUES
 (3, (SELECT tipo_movimiento_id FROM tipo_movimiento WHERE nombre = 'LICENCIA'),          NULL, '2026-07-28', '2026-07-30', 'Licencia por motivos de salud'),
 (4, (SELECT tipo_movimiento_id FROM tipo_movimiento WHERE nombre = 'COMISION_SERVICIO'), 3,    '2026-08-03', '2026-08-07', 'Apoyo en campaña de vacunación'),
 (5, (SELECT tipo_movimiento_id FROM tipo_movimiento WHERE nombre = 'VACACIONES'),        NULL, '2026-08-10', '2026-08-24', 'Descanso físico anual');

INSERT INTO log_auditoria (usuario_id, entidad, registro_id, accion, detalle) VALUES
 (1, 'personal', 6, 'REGISTRAR_PERSONAL', 'Alta del trabajador con DNI 48521369'),
 (1, 'movimiento_institucional', 1, 'REGISTRAR_MOVIMIENTO', 'Licencia registrada en estado PENDIENTE'),
 (2, 'usuario', 3, 'CREAR_USUARIO', 'Cuenta rhuaman creada y asociada al rol JEFE_AREA');


