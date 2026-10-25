-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 02 - Funciones PL/pgSQL (validación de citas, transiciones, historial, disponibilidad)
-- =====================================================================
-- ---------------------------------------------------------------------
-- FUNCIONES Y TRIGGERS (reglas de negocio protegidas en la BD)
-- ---------------------------------------------------------------------

-- Valida RN04, RN05, RN06 y RN08 antes de insertar o reprogramar una cita
CREATE OR REPLACE FUNCTION fn_validar_cita() RETURNS trigger AS $$
DECLARE
    v_estado_medico VARCHAR(10);
    v_estado_pac    VARCHAR(10);
    v_ok            BOOLEAN;
BEGIN
    -- Solo se valida al crear o al cambiar fecha/hora/médico
    IF TG_OP = 'UPDATE' AND NEW.fecha = OLD.fecha AND NEW.hora_inicio = OLD.hora_inicio
       AND NEW.id_medico = OLD.id_medico THEN
        RETURN NEW;
    END IF;

    SELECT estado INTO v_estado_medico FROM medico WHERE id_medico = NEW.id_medico;
    IF v_estado_medico <> 'activo' THEN
        RAISE EXCEPTION 'El médico está inactivo y no puede recibir nuevas citas';
    END IF;

    SELECT estado INTO v_estado_pac FROM paciente WHERE id_paciente = NEW.id_paciente;
    IF v_estado_pac <> 'activo' THEN
        RAISE EXCEPTION 'El paciente está inactivo';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM medico_especialidad
                   WHERE id_medico = NEW.id_medico AND id_especialidad = NEW.id_especialidad) THEN
        RAISE EXCEPTION 'El médico no atiende la especialidad seleccionada';
    END IF;

    IF (NEW.fecha + NEW.hora_inicio) < now() THEN
        RAISE EXCEPTION 'No se pueden registrar citas en fechas u horas pasadas';
    END IF;

    IF EXISTS (SELECT 1 FROM bloqueo_agenda
               WHERE fecha = NEW.fecha AND (id_medico IS NULL OR id_medico = NEW.id_medico)) THEN
        RAISE EXCEPTION 'La fecha seleccionada está bloqueada en la agenda';
    END IF;

    -- La cita debe caer dentro de un horario de atención y respetar el intervalo configurado
    SELECT EXISTS (
        SELECT 1 FROM horario_atencion h
        WHERE h.id_medico  = NEW.id_medico
          AND h.estado     = 'activo'
          AND h.dia_semana = EXTRACT(ISODOW FROM NEW.fecha)
          AND NEW.hora_inicio >= h.hora_inicio
          AND NEW.hora_fin    <= h.hora_fin
          AND NEW.hora_fin - NEW.hora_inicio = make_interval(mins => h.duracion_cita)
          AND (EXTRACT(EPOCH FROM (NEW.hora_inicio - h.hora_inicio)) / 60)::int % h.duracion_cita = 0
    ) INTO v_ok;

    IF NOT v_ok THEN
        RAISE EXCEPTION 'La cita está fuera del horario de atención del médico o no respeta el intervalo configurado';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Controla las transiciones de estado válidas (RF10)
CREATE OR REPLACE FUNCTION fn_transicion_estado() RETURNS trigger AS $$
BEGIN
    IF NEW.estado = OLD.estado THEN
        -- Reprogramación: solo citas Programadas o Confirmadas
        IF (NEW.fecha <> OLD.fecha OR NEW.hora_inicio <> OLD.hora_inicio)
           AND OLD.estado NOT IN ('Programada','Confirmada') THEN
            RAISE EXCEPTION 'Una cita % no puede reprogramarse', OLD.estado;
        END IF;
        RETURN NEW;
    END IF;

    IF NOT (
        (OLD.estado = 'Programada' AND NEW.estado IN ('Confirmada','Cancelada','Atendida','Inasistencia')) OR
        (OLD.estado = 'Confirmada' AND NEW.estado IN ('Atendida','Cancelada','Inasistencia'))
    ) THEN
        RAISE EXCEPTION 'Transición de estado no permitida: % -> %', OLD.estado, NEW.estado;
    END IF;

    IF NEW.estado IN ('Atendida','Inasistencia') AND (NEW.fecha + NEW.hora_inicio) > now() THEN
        RAISE EXCEPTION 'No se puede registrar asistencia de una cita futura';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Registra la trazabilidad de cada cambio de estado o reprogramación (RF12)
CREATE OR REPLACE FUNCTION fn_historial_cita() RETURNS trigger AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO historial_estado_cita (id_cita, estado_anterior, estado_nuevo, observacion, id_usuario)
        VALUES (NEW.id_cita, NULL, NEW.estado, 'Registro de la cita', NEW.id_usuario_registro);
    ELSIF NEW.estado <> OLD.estado OR NEW.fecha <> OLD.fecha OR NEW.hora_inicio <> OLD.hora_inicio THEN
        INSERT INTO historial_estado_cita (id_cita, estado_anterior, estado_nuevo, fecha_anterior,
                                           hora_anterior, observacion, id_usuario)
        VALUES (NEW.id_cita, OLD.estado, NEW.estado, OLD.fecha, OLD.hora_inicio,
                CASE WHEN NEW.estado <> OLD.estado THEN 'Cambio de estado' ELSE 'Reprogramación' END,
                NULLIF(current_setting('app.id_usuario', true), '')::int);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- RN10 / RN11: no se eliminan físicamente citas, pacientes ni médicos con historial
CREATE OR REPLACE FUNCTION fn_impedir_borrado() RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'No se permite eliminar registros de %; utilice la desactivación', TG_TABLE_NAME;
END;
$$ LANGUAGE plpgsql;

-- ---------------------------------------------------------------------
-- FUNCIÓN: horarios disponibles de un médico en una fecha (RF05, RF06)
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_horarios_disponibles(p_id_medico INT, p_fecha DATE)
RETURNS TABLE (hora_inicio TIME, hora_fin TIME) AS $$
    SELECT s.ini::time, (s.ini + make_interval(mins => h.duracion_cita))::time
    FROM horario_atencion h
    CROSS JOIN LATERAL generate_series(
        p_fecha + h.hora_inicio,
        p_fecha + h.hora_fin - make_interval(mins => h.duracion_cita),
        make_interval(mins => h.duracion_cita)) AS s(ini)
    JOIN medico m ON m.id_medico = h.id_medico AND m.estado = 'activo'
    WHERE h.id_medico = p_id_medico
      AND h.estado = 'activo'
      AND h.dia_semana = EXTRACT(ISODOW FROM p_fecha)
      AND s.ini > now()
      AND NOT EXISTS (SELECT 1 FROM bloqueo_agenda b
                      WHERE b.fecha = p_fecha AND (b.id_medico IS NULL OR b.id_medico = p_id_medico))
      AND NOT EXISTS (SELECT 1 FROM cita c
                      WHERE c.id_medico = p_id_medico AND c.fecha = p_fecha
                        AND c.estado <> 'Cancelada'
                        AND tsrange(p_fecha + c.hora_inicio, p_fecha + c.hora_fin)
                            && tsrange(s.ini, s.ini + make_interval(mins => h.duracion_cita)))
    ORDER BY 1;
$$ LANGUAGE sql STABLE;

-- ---------------------------------------------------------------------
