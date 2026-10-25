-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 05 - Datos iniciales: roles, permisos, administrador y especialidades
-- =====================================================================
INSERT INTO rol (nombre, descripcion) VALUES
 ('Administrador','Configura y supervisa el sistema'),
 ('Recepcionista','Gestiona pacientes y citas'),
 ('Medico','Consulta su agenda y registra atenciones'),
 ('Paciente','Gestiona su perfil y sus citas desde el portal');

INSERT INTO permiso (codigo, descripcion) VALUES
 ('usuario.gestionar','Registrar y administrar usuarios'),
 ('rol.gestionar','Asignar roles y permisos'),
 ('paciente.gestionar','Registrar, modificar y desactivar pacientes'),
 ('medico.gestionar','Registrar, modificar y desactivar médicos'),
 ('especialidad.gestionar','Administrar especialidades'),
 ('horario.gestionar','Registrar horarios de atención'),
 ('cita.programar','Programar, reprogramar y cancelar citas'),
 ('cita.asistencia','Registrar asistencia o inasistencia'),
 ('cita.atender','Marcar una cita como atendida'),
 ('agenda.consultar','Consultar la agenda médica'),
 ('reporte.generar','Generar reportes administrativos'),
 ('perfil.paciente','Gestionar el perfil propio y reservar citas');

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT 1, id_permiso FROM permiso WHERE codigo NOT IN ('perfil.paciente','cita.atender');
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT 2, id_permiso FROM permiso WHERE codigo IN ('paciente.gestionar','cita.programar','cita.asistencia','agenda.consultar','reporte.generar');
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT 3, id_permiso FROM permiso WHERE codigo IN ('agenda.consultar','cita.atender');
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT 4, id_permiso FROM permiso WHERE codigo = 'perfil.paciente';

-- Usuario administrador inicial (contraseña: Clinica2026*  -> cambiarla al primer ingreso)
INSERT INTO usuario (id_rol, username, correo, password_hash) VALUES
 (1,'admin','admin@vidasalud.bo','$2y$10$ecgkFu2CBDODAsgbTDbipeZQjLhxl.J6Nf/ft3T/V6E3tCObonJsO');

INSERT INTO especialidad (nombre, descripcion) VALUES
 ('Medicina general','Atención primaria y consulta general'),
 ('Pediatría','Atención de niños y adolescentes'),
 ('Ginecología','Salud de la mujer'),
 ('Odontología','Salud bucal'),
 ('Cardiología','Enfermedades del corazón');

