-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 03 - Triggers: enganchan las funciones a las tablas
-- =====================================================================
CREATE TRIGGER tg_validar_cita
BEFORE INSERT OR UPDATE OF fecha, hora_inicio, hora_fin, id_medico ON cita
FOR EACH ROW EXECUTE FUNCTION fn_validar_cita();

CREATE TRIGGER tg_transicion_estado
BEFORE UPDATE ON cita
FOR EACH ROW EXECUTE FUNCTION fn_transicion_estado();

CREATE TRIGGER tg_historial_cita
AFTER INSERT OR UPDATE ON cita
FOR EACH ROW EXECUTE FUNCTION fn_historial_cita();

CREATE TRIGGER tg_no_borrar_cita     BEFORE DELETE ON cita     FOR EACH ROW EXECUTE FUNCTION fn_impedir_borrado();

CREATE TRIGGER tg_no_borrar_paciente BEFORE DELETE ON paciente FOR EACH ROW EXECUTE FUNCTION fn_impedir_borrado();

CREATE TRIGGER tg_no_borrar_medico   BEFORE DELETE ON medico   FOR EACH ROW EXECUTE FUNCTION fn_impedir_borrado();
