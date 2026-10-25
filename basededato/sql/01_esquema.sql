-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 01 - Esquema: extensiones, tablas, restricciones e índices
-- =====================================================================
CREATE EXTENSION IF NOT EXISTS btree_gist;   -- necesaria para la restricción de exclusión de citas

-- ---------------------------------------------------------------------
-- PAQUETE SEGURIDAD
-- ---------------------------------------------------------------------
CREATE TABLE rol (
    id_rol        SERIAL PRIMARY KEY,
    nombre        VARCHAR(30)  NOT NULL UNIQUE,
    descripcion   VARCHAR(150),
    estado        VARCHAR(10)  NOT NULL DEFAULT 'activo'
                  CHECK (estado IN ('activo','inactivo'))
);

CREATE TABLE permiso (
    id_permiso    SERIAL PRIMARY KEY,
    codigo        VARCHAR(40)  NOT NULL UNIQUE,      -- p. ej. 'cita.programar'
    descripcion   VARCHAR(150) NOT NULL
);

CREATE TABLE rol_permiso (
    id_rol        INT NOT NULL REFERENCES rol(id_rol),
    id_permiso    INT NOT NULL REFERENCES permiso(id_permiso),
    PRIMARY KEY (id_rol, id_permiso)
);

CREATE TABLE usuario (
    id_usuario     SERIAL PRIMARY KEY,
    id_rol         INT          NOT NULL REFERENCES rol(id_rol),
    username       VARCHAR(40)  NOT NULL UNIQUE,
    correo         VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,            -- generado con password_hash() de PHP
    estado         VARCHAR(10)  NOT NULL DEFAULT 'activo'
                   CHECK (estado IN ('activo','inactivo','bloqueado')),
    intentos_fallidos SMALLINT  NOT NULL DEFAULT 0,
    ultimo_acceso  TIMESTAMP
);

CREATE TABLE bitacora (
    id_bitacora   BIGSERIAL PRIMARY KEY,
    id_usuario    INT REFERENCES usuario(id_usuario),
    accion        VARCHAR(60)  NOT NULL,
    tabla         VARCHAR(40),
    id_registro   INT,
    detalle       TEXT,
    ip            VARCHAR(45),
    fecha         TIMESTAMP    NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------
-- PAQUETE PERSONAS (pacientes, médicos y especialidades)
-- ---------------------------------------------------------------------
CREATE TABLE paciente (
    id_paciente      SERIAL PRIMARY KEY,
    ci               BIGINT       UNIQUE CHECK (ci > 0),  -- RN01: CI único cuando se proporciona
    nombres          VARCHAR(60)  NOT NULL,
    apellidos        VARCHAR(60)  NOT NULL,
    fecha_nacimiento DATE         NOT NULL CHECK (fecha_nacimiento <= CURRENT_DATE),
    sexo             CHAR(1)      CHECK (sexo IN ('M','F','O')),
    telefono         VARCHAR(20)  NOT NULL,
    correo           VARCHAR(100),
    direccion        VARCHAR(150),
    tipo_sangre      VARCHAR(3)   CHECK (tipo_sangre IN ('A+','A-','B+','B-','AB+','AB-','O+','O-')),
    alergias         TEXT,
    enfermedad_cronica TEXT,
    antecedentes     TEXT,
    id_usuario       INT UNIQUE REFERENCES usuario(id_usuario),   -- cuenta del portal (opcional)
    estado           VARCHAR(10)  NOT NULL DEFAULT 'activo'
                     CHECK (estado IN ('activo','inactivo'))
);

CREATE TABLE contacto_emergencia (
    id_contacto   SERIAL PRIMARY KEY,
    id_paciente   INT          NOT NULL REFERENCES paciente(id_paciente),
    nombre        VARCHAR(100) NOT NULL,
    parentesco    VARCHAR(30)  NOT NULL,
    telefono      VARCHAR(20)  NOT NULL
);

CREATE TABLE especialidad (
    id_especialidad SERIAL PRIMARY KEY,
    nombre          VARCHAR(60)  NOT NULL,
    descripcion     VARCHAR(200),
    estado          VARCHAR(10)  NOT NULL DEFAULT 'activo'
                    CHECK (estado IN ('activo','inactivo'))
);
-- RF04: no se permiten dos especialidades con el mismo nombre normalizado
CREATE UNIQUE INDEX ux_especialidad_nombre ON especialidad (lower(trim(nombre)));

CREATE TABLE medico (
    id_medico             SERIAL PRIMARY KEY,
    registro_profesional  VARCHAR(20)  NOT NULL UNIQUE,
    nombres               VARCHAR(60)  NOT NULL,
    apellidos             VARCHAR(60)  NOT NULL,
    telefono              VARCHAR(20),
    correo                VARCHAR(100),
    id_usuario            INT UNIQUE REFERENCES usuario(id_usuario),
    estado                VARCHAR(10)  NOT NULL DEFAULT 'activo'
                          CHECK (estado IN ('activo','inactivo'))
);

CREATE TABLE medico_especialidad (
    id_medico        INT NOT NULL REFERENCES medico(id_medico),
    id_especialidad  INT NOT NULL REFERENCES especialidad(id_especialidad),
    PRIMARY KEY (id_medico, id_especialidad)
);

-- ---------------------------------------------------------------------
-- PAQUETE AGENDA (horarios de atención)
-- ---------------------------------------------------------------------
CREATE TABLE horario_atencion (
    id_horario     SERIAL PRIMARY KEY,
    id_medico      INT      NOT NULL REFERENCES medico(id_medico),
    dia_semana     SMALLINT NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),  -- 1 = lunes (ISODOW)
    hora_inicio    TIME     NOT NULL,
    hora_fin       TIME     NOT NULL,
    duracion_cita  SMALLINT NOT NULL DEFAULT 30 CHECK (duracion_cita IN (15,20,30,45,60)),
    estado         VARCHAR(10) NOT NULL DEFAULT 'activo'
                   CHECK (estado IN ('activo','inactivo')),
    CONSTRAINT ck_horario_rango CHECK (hora_fin > hora_inicio),
    -- un médico no puede tener dos bloques de horario superpuestos el mismo día
    CONSTRAINT ex_horario_superpuesto EXCLUDE USING gist (
        id_medico WITH =, dia_semana WITH =,
        tsrange(DATE '2000-01-01' + hora_inicio, DATE '2000-01-01' + hora_fin) WITH &&
    ) WHERE (estado = 'activo')
);

CREATE TABLE bloqueo_agenda (
    id_bloqueo   SERIAL PRIMARY KEY,
    id_medico    INT REFERENCES medico(id_medico),   -- NULL = feriado para toda la clínica
    fecha        DATE NOT NULL,
    motivo       VARCHAR(100) NOT NULL
);

-- ---------------------------------------------------------------------
-- PAQUETE CITAS
-- ---------------------------------------------------------------------
CREATE TABLE cita (
    id_cita            SERIAL PRIMARY KEY,
    id_paciente        INT  NOT NULL REFERENCES paciente(id_paciente),
    id_medico          INT  NOT NULL REFERENCES medico(id_medico),
    id_especialidad    INT  NOT NULL REFERENCES especialidad(id_especialidad),
    fecha              DATE NOT NULL,
    hora_inicio        TIME NOT NULL,
    hora_fin           TIME NOT NULL,
    motivo             VARCHAR(200),
    modalidad          VARCHAR(12) NOT NULL DEFAULT 'presencial'
                       CHECK (modalidad IN ('presencial','virtual')),
    estado             VARCHAR(12) NOT NULL DEFAULT 'Programada'
                       CHECK (estado IN ('Programada','Confirmada','Atendida','Cancelada','Inasistencia')),
    motivo_cancelacion VARCHAR(200),
    id_usuario_registro INT REFERENCES usuario(id_usuario),   -- usuario que registra la cita
    fecha_creacion     TIMESTAMP NOT NULL DEFAULT now(),
    fecha_actualizacion TIMESTAMP,
    CONSTRAINT ck_cita_rango CHECK (hora_fin > hora_inicio),
    CONSTRAINT ck_cancelacion CHECK (estado <> 'Cancelada' OR motivo_cancelacion IS NOT NULL),
    -- RF07 / RN07: un médico no puede tener citas superpuestas.
    -- Las citas canceladas no ocupan horario (RN09), por eso se excluyen.
    CONSTRAINT ex_cita_superpuesta EXCLUDE USING gist (
        id_medico WITH =,
        tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&
    ) WHERE (estado <> 'Cancelada')
);

CREATE TABLE historial_estado_cita (
    id_historial     SERIAL PRIMARY KEY,
    id_cita          INT NOT NULL REFERENCES cita(id_cita),
    estado_anterior  VARCHAR(12),
    estado_nuevo     VARCHAR(12) NOT NULL,
    fecha_anterior   DATE,
    hora_anterior    TIME,
    observacion      VARCHAR(200),
    id_usuario       INT REFERENCES usuario(id_usuario),
    fecha            TIMESTAMP NOT NULL DEFAULT now()
);

-- Índices de apoyo a las consultas frecuentes
CREATE INDEX ix_cita_medico_fecha   ON cita (id_medico, fecha);
CREATE INDEX ix_cita_paciente       ON cita (id_paciente);
CREATE INDEX ix_cita_estado_fecha   ON cita (estado, fecha);
CREATE INDEX ix_paciente_nombre     ON paciente (lower(apellidos), lower(nombres));
CREATE INDEX ix_horario_medico_dia  ON horario_atencion (id_medico, dia_semana);

