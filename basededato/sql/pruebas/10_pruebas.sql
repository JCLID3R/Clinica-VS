-- =====================================================================
-- Pruebas de la base de datos (pruebas mínimas del enunciado, nivel BD)
-- Cada prueba que debe fallar se ejecuta dentro de un bloque que captura el error.
-- =====================================================================
\set ON_ERROR_STOP off
\echo '--- P1: Registrar un paciente nuevo'
INSERT INTO paciente (ci, nombres, apellidos, fecha_nacimiento, sexo, telefono)
VALUES (9001122,'Andrés','Salvatierra Cuéllar','1999-07-21','M','78899001');
SELECT id_paciente, ci, nombres, apellidos, estado FROM paciente WHERE ci = 9001122;

\echo '--- P2: Registrar un paciente con CI duplicado'
INSERT INTO paciente (ci, nombres, apellidos, fecha_nacimiento, sexo, telefono)
VALUES (9001122,'Otro','Paciente','2000-01-01','M','70000000');

\echo '--- P3: Programar una cita en horario disponible (lunes 16/11/2026 09:00-09:30, Dr. Pérez)'
SELECT * FROM fn_horarios_disponibles(1, DATE '2026-11-16') LIMIT 4;
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, motivo, id_usuario_registro)
VALUES (1, 1, 1, '2026-11-16', '09:00', '09:30', 'Consulta general', 2)
RETURNING 'CM-' || lpad(id_cita::text, 4, '0') AS codigo, fecha, hora_inicio, hora_fin, estado;

\echo '--- P4: Programar otra cita superpuesta para el mismo médico'
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, id_usuario_registro)
VALUES (2, 1, 1, '2026-11-16', '09:00', '09:30', 2);

\echo '--- P5: Intentar reservar fuera del horario del médico (lunes 15:00)'
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, id_usuario_registro)
VALUES (2, 1, 1, '2026-11-16', '15:00', '15:30', 2);

\echo '--- P5b: Intentar reservar en una fecha pasada'
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, id_usuario_registro)
VALUES (2, 1, 1, '2026-10-05', '09:00', '09:30', 2);

\echo '--- P6: Cancelar una cita (cambia a Cancelada y conserva el historial)'
UPDATE cita SET estado = 'Cancelada', motivo_cancelacion = 'Solicitud del paciente'
WHERE id_cita = (SELECT id_cita FROM cita WHERE fecha = '2026-11-16' AND hora_inicio = '09:00');
SELECT 'CM-' || lpad(id_cita::text, 4, '0') AS codigo, estado, motivo_cancelacion FROM cita WHERE fecha = '2026-11-16';
SELECT estado_anterior, estado_nuevo, observacion FROM historial_estado_cita
WHERE id_cita = (SELECT id_cita FROM cita WHERE fecha = '2026-11-16' AND hora_inicio = '09:00') ORDER BY id_historial;
\echo '--- P6b: El horario liberado vuelve a estar disponible'
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, id_usuario_registro)
VALUES (2, 1, 1, '2026-11-16', '09:00', '09:30', 2) RETURNING 'CM-' || lpad(id_cita::text, 4, '0') AS codigo, estado;
\echo '--- P6c: Una cita cancelada no puede marcarse como atendida'
UPDATE cita SET estado = 'Atendida' WHERE estado = 'Cancelada' AND fecha = '2026-11-16';
\echo '--- P6d: No se elimina físicamente una cita'
DELETE FROM cita WHERE fecha = '2026-11-16';

\echo '--- P7: Consultar el historial de un paciente'
SELECT codigo, fecha, hora_inicio, medico, especialidad, estado
FROM v_agenda_medica WHERE id_paciente = 1 ORDER BY fecha DESC, hora_inicio DESC;

\echo '--- P8: Buscar las citas de un médico por fecha'
SELECT hora_inicio, codigo, paciente, especialidad, estado
FROM v_agenda_medica WHERE id_medico = 1 AND fecha = '2026-09-07' ORDER BY hora_inicio;

\echo '--- P10: Datos inválidos (hora final anterior a la inicial en un horario)'
INSERT INTO horario_atencion (id_medico, dia_semana, hora_inicio, hora_fin) VALUES (1, 2, '12:00', '08:00');
\echo '--- P10b: Especialidad con nombre duplicado normalizado'
INSERT INTO especialidad (nombre) VALUES ('  PEDIATRÍA ');
\echo '--- P10c: Cita con un médico inactivo'
UPDATE medico SET estado = 'inactivo' WHERE id_medico = 3;
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin)
VALUES (1, 3, 4, '2026-11-20', '08:00', '08:30');

\echo '--- Reporte: cantidad de citas por estado en un período'
SELECT sum(total) total, sum(atendidas) atendidas, sum(canceladas) canceladas,
       sum(inasistencias) inasistencias, sum(pendientes) pendientes
FROM v_resumen_estados WHERE fecha BETWEEN '2026-09-01' AND '2026-11-30';
