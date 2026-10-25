-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 06 - Datos de prueba (ficticios). Contraseña de todos: Clinica2026*
-- =====================================================================
INSERT INTO usuario (id_rol, username, correo, password_hash) VALUES
 (2,'recepcion1','recepcion@vidasalud.bo','$2y$10$ecgkFu2CBDODAsgbTDbipeZQjLhxl.J6Nf/ft3T/V6E3tCObonJsO'),
 (3,'jperez','jperez@vidasalud.bo','$2y$10$ecgkFu2CBDODAsgbTDbipeZQjLhxl.J6Nf/ft3T/V6E3tCObonJsO'),
 (3,'arojas','arojas@vidasalud.bo','$2y$10$ecgkFu2CBDODAsgbTDbipeZQjLhxl.J6Nf/ft3T/V6E3tCObonJsO'),
 (4,'mlopez','maria.lopez@correo.com','$2y$10$ecgkFu2CBDODAsgbTDbipeZQjLhxl.J6Nf/ft3T/V6E3tCObonJsO');

INSERT INTO medico (registro_profesional, nombres, apellidos, telefono, correo, id_usuario) VALUES
 ('MP-12345','Juan','Pérez Gómez','70011122','jperez@vidasalud.bo',3),
 ('MP-23456','Ana','Rojas Suárez','70022233','arojas@vidasalud.bo',4),
 ('MP-34567','Carlos','Méndez Vaca','70033344','cmendez@vidasalud.bo',NULL);

INSERT INTO medico_especialidad VALUES (1,1),(2,2),(2,1),(3,4);

-- Horarios: Médico A lunes 08-12 y miércoles 14-18; Médico B martes 09-13 (ejemplo del enunciado)
INSERT INTO horario_atencion (id_medico, dia_semana, hora_inicio, hora_fin, duracion_cita) VALUES
 (1,1,'08:00','12:00',30),(1,3,'14:00','18:00',30),
 (2,2,'09:00','13:00',30),(2,4,'09:00','13:00',30),
 (3,5,'08:00','12:00',30);

INSERT INTO paciente (ci, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, tipo_sangre, alergias, id_usuario) VALUES
 (7845123,'María','López Arteaga','1990-04-12','F','76543210','maria.lopez@correo.com','Av. Banzer 4to anillo','O+','Penicilina',5),
 (6523147,'Pedro','Gutiérrez Flores','1985-09-30','M','71234567',NULL,'B/ Equipetrol','A+',NULL,NULL),
 (8541236,'Lucía','Vargas Ribera','2018-02-05','F','72345678',NULL,NULL,'O-',NULL,NULL);

INSERT INTO contacto_emergencia (id_paciente, nombre, parentesco, telefono) VALUES
 (1,'Rosa Arteaga','Madre','77711122'),
 (3,'Jorge Vargas','Padre','77722233');

-- Citas históricas (se desactiva temporalmente la validación de fecha pasada solo para la carga inicial)
ALTER TABLE cita DISABLE TRIGGER tg_validar_cita;
ALTER TABLE cita DISABLE TRIGGER tg_transicion_estado;
INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, motivo, estado, motivo_cancelacion, id_usuario_registro) VALUES
 (1,1,1,'2026-09-07','09:00','09:30','Control general','Atendida',NULL,2),
 (2,1,1,'2026-09-07','09:30','10:00','Dolor de cabeza','Inasistencia',NULL,2),
 (3,2,2,'2026-09-08','10:00','10:30','Control pediátrico','Atendida',NULL,2),
 (1,2,1,'2026-09-15','09:00','09:30','Chequeo','Cancelada','El paciente viajó',2);
ALTER TABLE cita ENABLE TRIGGER tg_validar_cita;
ALTER TABLE cita ENABLE TRIGGER tg_transicion_estado;
