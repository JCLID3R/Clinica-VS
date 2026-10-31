-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 07 - Datos de demostración generados automáticamente (poblado / seeding)
--
-- Se ejecuta DESPUÉS de 01 a 05 (con o sin 06). Genera datos coherentes con las reglas del sistema:
--   * 2 especialidades más y 8 médicos más (varios médicos por especialidad, cada uno con sus turnos)
--   * 150 pacientes con datos ficticios y contactos de emergencia
--   * citas en los últimos 60 días y en los próximos 14, siempre en fichas válidas del horario
--     de cada médico, sin superposiciones del médico ni del paciente y con estados realistas
-- Las fechas se calculan a partir del día en que se ejecuta, así el panel siempre tiene datos.
-- Es seguro ejecutarlo más de una vez: si ya se cargó, no hace nada.
-- No modifica la estructura de la base (solo INSERT).
-- =====================================================================

DO $$
DECLARE
    -- nombres ficticios
    nombres_h  text[] := ARRAY['Jorge','Carlos','Luis','Miguel','José','Pablo','Diego','Fernando','Ricardo','Marco',
                               'Daniel','Andrés','Roberto','Sergio','Álvaro','Gonzalo','Rodrigo','Mauricio','Iván','Raúl'];
    nombres_m  text[] := ARRAY['María','Ana','Lucía','Carla','Sofía','Valeria','Daniela','Gabriela','Paola','Fernanda',
                               'Rosa','Patricia','Claudia','Verónica','Mónica','Silvia','Andrea','Natalia','Jimena','Camila'];
    apellidos  text[] := ARRAY['Mamani','Quispe','Choque','Flores','Rojas','Vargas','Gutiérrez','Fernández','López','Pérez',
                               'Suárez','Justiniano','Áñez','Saucedo','Rivero','Salvatierra','Montaño','Vaca','Paz','Mendoza',
                               'Cuéllar','Ribera','Arteaga','Soliz','Peña','Aguilera','Moreno','Durán','Velasco','Céspedes'];
    zonas      text[] := ARRAY['Av. Banzer 3er anillo','B/ Equipetrol','Av. Alemana 4to anillo','B/ Las Palmas','Av. Grigotá',
                               'B/ Hamacas','Av. Santos Dumont','Plan 3000','Villa 1ro de Mayo','Av. Piraí 5to anillo'];
    sangre     text[] := ARRAY['O+','O+','O+','A+','A+','B+','AB+','O-','A-','B-'];
    parentesco text[] := ARRAY['Madre','Padre','Esposo(a)','Hermano(a)','Hijo(a)'];
    motivos    text[] := ARRAY['Control general','Dolor de cabeza','Fiebre','Control de presión','Resultados de laboratorio',
                               'Dolor abdominal','Tos persistente','Control pediátrico','Chequeo anual','Dolor de muelas',
                               'Limpieza dental','Control prenatal','Dolor de espalda','Revisión de tratamiento','Mareos'];
    cancelaciones text[] := ARRAY['El paciente viajó','Motivos de trabajo','Se sintió mejor','Cambio de médico',
                                  'Problemas de transporte','Reprogramará más adelante'];

    i int; h record; d date; ficha time; n_fichas int; k int;
    v_pac int; v_esp int; v_estado text; v_prob numeric; v_creada timestamp;
    v_es_hombre boolean; v_nombre text; v_ini timestamp;
    pacientes int[];
    v_usuario int;
BEGIN
    IF EXISTS (SELECT 1 FROM medico WHERE registro_profesional = 'MP-40001') THEN
        RAISE NOTICE '07_datos_demo ya fue cargado; no se hace nada.';
        RETURN;
    END IF;

    PERFORM setseed(0.2026);
    -- usuario que registra las citas: la recepcionista de prueba (06) o, si no existe, el administrador
    v_usuario := coalesce((SELECT id_usuario FROM usuario WHERE username = 'recepcion1'),
                          (SELECT id_usuario FROM usuario WHERE username = 'admin'));   -- mismos datos cada vez que se carga en una base nueva

    -- ---------------------------------------------------------------- especialidades
    INSERT INTO especialidad (nombre, descripcion)
    SELECT v.n, v.d FROM (VALUES ('Dermatología','Enfermedades de la piel'),
                                 ('Traumatología','Lesiones de huesos, músculos y articulaciones')) AS v(n, d)
    WHERE NOT EXISTS (SELECT 1 FROM especialidad e WHERE lower(trim(e.nombre)) = lower(v.n));

    -- ---------------------------------------------------------------- médicos (varios por especialidad)
    INSERT INTO medico (registro_profesional, nombres, apellidos, telefono, correo) VALUES
     ('MP-40001','Roberto','Mendoza Salazar','70044455','rmendoza@vidasalud.bo'),
     ('MP-40002','Patricia','Justiniano Vaca','70055566','pjustiniano@vidasalud.bo'),
     ('MP-40003','Fernando','Áñez Rivero','70066677','fanez@vidasalud.bo'),
     ('MP-40004','Claudia','Suárez Peña','70077788','csuarez@vidasalud.bo'),
     ('MP-40005','Mauricio','Saucedo Paz','70088899','msaucedo@vidasalud.bo'),
     ('MP-40006','Gabriela','Montaño Flores','70099900','gmontano@vidasalud.bo'),
     ('MP-40007','Sergio','Velasco Durán','70011223','svelasco@vidasalud.bo'),
     ('MP-40008','Valeria','Cuéllar Soliz','70022334','vcuellar@vidasalud.bo');

    -- especialidades de cada médico nuevo (Medicina general y Pediatría quedan con 3 médicos)
    INSERT INTO medico_especialidad (id_medico, id_especialidad)
    SELECT m.id_medico, e.id_especialidad
    FROM (VALUES ('MP-40001','Medicina general'), ('MP-40002','Pediatría'), ('MP-40003','Cardiología'),
                 ('MP-40004','Ginecología'), ('MP-40005','Traumatología'), ('MP-40006','Pediatría'),
                 ('MP-40007','Odontología'), ('MP-40008','Dermatología'), ('MP-40008','Medicina general')) AS v(reg, esp)
    JOIN medico m ON m.registro_profesional = v.reg
    JOIN especialidad e ON e.nombre = v.esp;

    -- turnos de 20 minutos (Mañana 08:00-12:00 = 12 fichas, Tarde 14:00-18:00 = 12 fichas)
    INSERT INTO horario_atencion (id_medico, dia_semana, hora_inicio, hora_fin, duracion_cita)
    SELECT m.id_medico, v.dia, v.ini::time, v.fin::time, 20
    FROM (VALUES ('MP-40001',1,'14:00','18:00'), ('MP-40001',3,'14:00','18:00'), ('MP-40001',5,'08:00','12:00'),
                 ('MP-40002',1,'08:00','12:00'), ('MP-40002',3,'08:00','12:00'), ('MP-40002',5,'14:00','18:00'),
                 ('MP-40003',2,'14:00','18:00'), ('MP-40003',4,'08:00','12:00'),
                 ('MP-40004',2,'08:00','12:00'), ('MP-40004',4,'14:00','18:00'),
                 ('MP-40005',1,'08:00','12:00'), ('MP-40005',3,'14:00','18:00'), ('MP-40005',5,'14:00','18:00'),
                 ('MP-40006',2,'08:00','12:00'), ('MP-40006',4,'08:00','12:00'), ('MP-40006',6,'08:00','12:00'),
                 ('MP-40007',2,'14:00','18:00'), ('MP-40007',4,'14:00','18:00'),
                 ('MP-40008',1,'08:00','12:00'), ('MP-40008',3,'08:00','12:00'), ('MP-40008',5,'08:00','12:00'))
         AS v(reg, dia, ini, fin)
    JOIN medico m ON m.registro_profesional = v.reg;

    -- ---------------------------------------------------------------- pacientes
    FOR i IN 1..150 LOOP
        v_es_hombre := random() < 0.5;
        v_nombre := CASE WHEN v_es_hombre THEN nombres_h[1 + floor(random() * 20)::int]
                         ELSE nombres_m[1 + floor(random() * 20)::int] END;
        INSERT INTO paciente (ci, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion,
                              tipo_sangre, alergias, enfermedad_cronica)
        VALUES (5100000 + i * 7919,                                   -- CI único de 7 dígitos
                v_nombre,
                apellidos[1 + floor(random() * 30)::int] || ' ' || apellidos[1 + floor(random() * 30)::int],
                (current_date - ((1 + floor(random() * 80)) * 365 + floor(random() * 365))::int),
                CASE WHEN v_es_hombre THEN 'M' ELSE 'F' END,
                (CASE WHEN random() < 0.5 THEN '7' ELSE '6' END) || lpad((floor(random() * 10000000))::text, 7, '0'),
                CASE WHEN random() < 0.6 THEN lower(translate(v_nombre, 'áéíóúÁÉÍÓÚñÑ', 'aeiouAEIOUnN')) || i || '@correo.com' END,
                zonas[1 + floor(random() * 10)::int],
                sangre[1 + floor(random() * 10)::int],
                CASE WHEN random() < 0.15 THEN (ARRAY['Penicilina','Aspirina','Mariscos','Polen'])[1 + floor(random() * 4)::int] END,
                CASE WHEN random() < 0.12 THEN (ARRAY['Hipertensión','Diabetes tipo 2','Asma'])[1 + floor(random() * 3)::int] END);
    END LOOP;

    INSERT INTO contacto_emergencia (id_paciente, nombre, parentesco, telefono)
    SELECT p.id_paciente,
           nombres_m[1 + floor(random() * 20)::int] || ' ' || split_part(p.apellidos, ' ', 1),
           parentesco[1 + floor(random() * 5)::int],
           '7' || lpad((floor(random() * 10000000))::text, 7, '0')
    FROM paciente p
    WHERE p.ci BETWEEN 5100000 AND 5100000 + 150 * 7919 AND random() < 0.6;

    SELECT array_agg(id_paciente) INTO pacientes FROM paciente WHERE estado = 'activo';

    -- ---------------------------------------------------------------- citas
    -- Fechas pasadas: se desactiva la validación solo durante la carga (igual que 06_datos_prueba.sql)
    ALTER TABLE cita DISABLE TRIGGER tg_validar_cita;
    ALTER TABLE cita DISABLE TRIGGER tg_transicion_estado;

    FOR d IN SELECT generate_series(current_date - 60, current_date + 14, interval '1 day')::date LOOP
        CONTINUE WHEN EXISTS (SELECT 1 FROM bloqueo_agenda b WHERE b.fecha = d AND b.id_medico IS NULL);

        FOR h IN SELECT ha.* FROM horario_atencion ha JOIN medico m ON m.id_medico = ha.id_medico
                 WHERE ha.estado = 'activo' AND m.estado = 'activo' AND ha.dia_semana = EXTRACT(ISODOW FROM d)
                   AND NOT EXISTS (SELECT 1 FROM bloqueo_agenda b WHERE b.fecha = d AND b.id_medico = ha.id_medico)
        LOOP
            -- probabilidad de que una ficha esté ocupada: alta en el pasado, baja en el futuro lejano
            v_prob := CASE WHEN d <= current_date THEN 0.40 ELSE greatest(0.10, 0.45 - (d - current_date) * 0.025) END;
            n_fichas := (EXTRACT(EPOCH FROM (h.hora_fin - h.hora_inicio)) / 60 / h.duracion_cita)::int;

            FOR k IN 0..n_fichas - 1 LOOP
                CONTINUE WHEN random() > v_prob;
                ficha := h.hora_inicio + make_interval(mins => k * h.duracion_cita);
                v_ini := d + ficha;

                -- la ficha no puede estar ocupada (por ejemplo por las citas de 06_datos_prueba.sql)
                CONTINUE WHEN EXISTS (SELECT 1 FROM cita c WHERE c.id_medico = h.id_medico AND c.fecha = d
                                        AND c.estado <> 'Cancelada'
                                        AND tsrange(d + c.hora_inicio, d + c.hora_fin)
                                            && tsrange(v_ini, v_ini + make_interval(mins => h.duracion_cita)));

                -- paciente al azar que no tenga otra cita a esa misma hora
                v_pac := pacientes[1 + floor(random() * array_length(pacientes, 1))::int];
                CONTINUE WHEN EXISTS (SELECT 1 FROM cita c WHERE c.id_paciente = v_pac AND c.fecha = d
                                        AND c.estado <> 'Cancelada' AND c.hora_inicio = ficha);

                SELECT me.id_especialidad INTO v_esp FROM medico_especialidad me
                WHERE me.id_medico = h.id_medico ORDER BY random() LIMIT 1;

                -- estado realista según si la cita ya pasó o no
                IF v_ini < now() THEN
                    v_estado := CASE WHEN random() < 0.80 THEN 'Atendida'
                                     WHEN random() < 0.55 THEN 'Inasistencia' ELSE 'Cancelada' END;
                ELSE
                    v_estado := CASE WHEN random() < 0.62 THEN 'Programada'
                                     WHEN random() < 0.80 THEN 'Confirmada' ELSE 'Cancelada' END;
                END IF;
                v_creada := least(v_ini - make_interval(days => 1 + floor(random() * 10)::int), now() - interval '1 hour');

                INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, motivo,
                                  modalidad, estado, motivo_cancelacion, id_usuario_registro, fecha_creacion)
                VALUES (v_pac, h.id_medico, v_esp, d, ficha, ficha + make_interval(mins => h.duracion_cita),
                        motivos[1 + floor(random() * 15)::int],
                        CASE WHEN random() < 0.85 THEN 'presencial' ELSE 'virtual' END,
                        v_estado,
                        CASE WHEN v_estado = 'Cancelada' THEN cancelaciones[1 + floor(random() * 6)::int] END,
                        v_usuario, v_creada);
            END LOOP;
        END LOOP;
    END LOOP;

    ALTER TABLE cita ENABLE TRIGGER tg_validar_cita;
    ALTER TABLE cita ENABLE TRIGGER tg_transicion_estado;

    RAISE NOTICE '07_datos_demo cargado: % médicos, % pacientes, % citas en total.',
        (SELECT count(*) FROM medico), (SELECT count(*) FROM paciente), (SELECT count(*) FROM cita);
END $$;

-- Resumen para comprobar la carga
SELECT estado, count(*) AS citas FROM cita GROUP BY estado ORDER BY 2 DESC;
