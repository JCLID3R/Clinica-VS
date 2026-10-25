-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 04 - Vistas para agenda, historial y reportes
-- =====================================================================
-- VISTAS PARA REPORTES (RF11, RF12, RF13)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_agenda_medica AS
SELECT c.id_cita, 'CM-' || lpad(c.id_cita::text, 4, '0') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado, c.motivo,
       m.id_medico, m.nombres || ' ' || m.apellidos AS medico,
       p.id_paciente, p.nombres || ' ' || p.apellidos AS paciente, p.ci,
       e.id_especialidad, e.nombre AS especialidad
FROM cita c
JOIN medico m       ON m.id_medico = c.id_medico
JOIN paciente p     ON p.id_paciente = c.id_paciente
JOIN especialidad e ON e.id_especialidad = c.id_especialidad;

CREATE OR REPLACE VIEW v_resumen_estados AS
SELECT fecha, id_medico, id_especialidad,
       count(*)                                         AS total,
       count(*) FILTER (WHERE estado = 'Atendida')      AS atendidas,
       count(*) FILTER (WHERE estado = 'Cancelada')     AS canceladas,
       count(*) FILTER (WHERE estado = 'Inasistencia')  AS inasistencias,
       count(*) FILTER (WHERE estado IN ('Programada','Confirmada')) AS pendientes
FROM cita
GROUP BY fecha, id_medico, id_especialidad;
