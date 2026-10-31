<?php
/**
 * Paquete personas — acceso a datos (consultas SQL parametrizadas con PDO).
 * Solo este archivo escribe SQL del paquete. Usa App\Nucleo\BaseDatos::consultar(), fila(), ejecutar() y transaccion().
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\Repository;

use App\Nucleo\BaseDatos;

// ---- CU06: inicio ----
function listarEspecialidades(string $filtro, string $estado, int $pagina, int $por_pagina): array
{
    $sql = "SELECT e.id_especialidad, e.nombre, e.descripcion, e.estado,
                   COUNT(me.id_medico) as cantidad_medicos
            FROM especialidad e
            LEFT JOIN medico_especialidad me ON me.id_especialidad = e.id_especialidad
                                                AND me.id_medico IN (SELECT id_medico FROM medico WHERE estado = 'activo')
            WHERE 1=1";
    $binds = [];

    if ($filtro) {
        $sql .= " AND lower(e.nombre) LIKE lower(:filtro)";
        $binds[':filtro'] = "%{$filtro}%";
    }

    if ($estado) {
        $sql .= " AND e.estado = :estado";
        $binds[':estado'] = $estado;
    }

    $sql .= " GROUP BY e.id_especialidad, e.nombre, e.descripcion, e.estado
              ORDER BY e.nombre ASC";

    $total = count(BaseDatos::consultar($sql, $binds));
    $offset = ($pagina - 1) * $por_pagina;

    $sql .= " LIMIT :limit OFFSET :offset";
    $binds[':limit'] = $por_pagina;
    $binds[':offset'] = $offset;

    $especialidades = BaseDatos::consultar($sql, $binds);

    return [
        'especialidades' => $especialidades,
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina,
        'total_paginas' => (int) ceil($total / $por_pagina),
    ];
}

function existeEspecialidadPorNombre(string $nombre): bool
{
    $result = BaseDatos::fila(
        "SELECT COUNT(*) as total FROM especialidad WHERE lower(trim(nombre)) = lower(trim(:nombre))",
        [':nombre' => $nombre]
    );
    return (int) $result['total'] > 0;
}

function registrarEspecialidad(string $nombre, ?string $descripcion): int
{
    $result = BaseDatos::fila(
        "INSERT INTO especialidad (nombre, descripcion, estado)
         VALUES (:nombre, :descripcion, 'activo')
         RETURNING id_especialidad",
        [':nombre' => $nombre, ':descripcion' => $descripcion]
    );
    return (int) $result['id_especialidad'];
}

function modificarEspecialidad(int $id, string $nombre, ?string $descripcion): void
{
    BaseDatos::ejecutar(
        "UPDATE especialidad SET nombre = :nombre, descripcion = :descripcion
         WHERE id_especialidad = :id",
        [':nombre' => $nombre, ':descripcion' => $descripcion, ':id' => $id]
    );
}

function cambiarEstadoEspecialidad(int $id, string $estado): void
{
    BaseDatos::ejecutar(
        "UPDATE especialidad SET estado = :estado WHERE id_especialidad = :id",
        [':estado' => $estado, ':id' => $id]
    );
}

function contarMedicosPorEspecialidad(int $id_especialidad, string $estado = null): int
{
    $sql = "SELECT COUNT(DISTINCT m.id_medico) as total
            FROM medico m
            JOIN medico_especialidad me ON me.id_medico = m.id_medico
            WHERE me.id_especialidad = :id";
    $binds = [':id' => $id_especialidad];

    if ($estado) {
        $sql .= " AND m.estado = :estado";
        $binds[':estado'] = $estado;
    }

    $result = BaseDatos::fila($sql, $binds);
    return (int) $result['total'];
}

function obtenerEspecialidadPorId(int $id): ?array
{
    $result = BaseDatos::fila(
        "SELECT id_especialidad, nombre, descripcion, estado FROM especialidad WHERE id_especialidad = :id",
        [':id' => $id]
    );
    return $result ?: null;
}

function registrarBitacora(int $id_usuario, string $accion, int $id_registro, string $detalle, string $tabla, string $ip): void
{
    BaseDatos::ejecutar(
        "INSERT INTO bitacora (id_usuario, accion, tabla, id_registro, detalle, ip, fecha)
         VALUES (:id_usuario, :accion, :tabla, :id_registro, :detalle, :ip, now())",
        [
            ':id_usuario' => $id_usuario,
            ':accion' => $accion,
            ':tabla' => $tabla,
            ':id_registro' => $id_registro,
            ':detalle' => $detalle,
            ':ip' => $ip,
        ]
    );
}
// ---- CU06: fin ----

// ---- CU05: inicio ----
function listarMedicos(string $buscar, string $estado, int $pagina, int $por_pagina): array
{
    $sql = "SELECT DISTINCT m.id_medico, m.registro_profesional, m.nombres, m.apellidos, m.telefono, m.correo, m.estado,
                   array_agg(DISTINCT me.id_especialidad) FILTER (WHERE me.id_especialidad IS NOT NULL) AS especialidades_ids,
                   array_agg(DISTINCT e.nombre ORDER BY e.nombre) FILTER (WHERE me.id_especialidad IS NOT NULL) AS especialidades_nombres
            FROM medico m
            LEFT JOIN medico_especialidad me ON me.id_medico = m.id_medico
            LEFT JOIN especialidad e ON e.id_especialidad = me.id_especialidad AND e.estado = 'activo'
            WHERE 1=1";
    $binds = [];

    if ($buscar) {
        $sql .= " AND (translate(lower(m.nombres || ' ' || m.apellidos), 'áéíóúüñ', 'aeiouun') LIKE translate(lower(:b1), 'áéíóúüñ', 'aeiouun')
                      OR lower(m.registro_profesional) LIKE lower(:b2)
                      OR EXISTS (SELECT 1 FROM medico_especialidad mex JOIN especialidad ex ON ex.id_especialidad = mex.id_especialidad
                                 WHERE mex.id_medico = m.id_medico AND translate(lower(ex.nombre), 'áéíóúüñ', 'aeiouun') LIKE translate(lower(:b3), 'áéíóúüñ', 'aeiouun')))";
        $binds[':b1'] = "%{$buscar}%";
        $binds[':b2'] = "%{$buscar}%";
        $binds[':b3'] = "%{$buscar}%";
    }

    if ($estado) {
        $sql .= " AND m.estado = :b4";
        $binds[':b4'] = $estado;
    }

    $sql .= " GROUP BY m.id_medico, m.registro_profesional, m.nombres, m.apellidos, m.telefono, m.correo, m.estado
              ORDER BY m.nombres ASC, m.apellidos ASC";

    $total = count(BaseDatos::consultar($sql, $binds));
    $offset = ($pagina - 1) * $por_pagina;

    $sql .= " LIMIT :limit OFFSET :offset";
    $binds[':limit'] = $por_pagina;
    $binds[':offset'] = $offset;

    $medicos = BaseDatos::consultar($sql, $binds);

    return [
        'medicos' => $medicos,
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina,
        'total_paginas' => (int) ceil($total / $por_pagina),
    ];
}

function medicoExisteRegistro(string $registroProf): bool
{
    $result = BaseDatos::fila(
        "SELECT COUNT(*) as total FROM medico WHERE lower(trim(registro_profesional)) = lower(trim(:registro))",
        [':registro' => $registroProf]
    );
    return (int) $result['total'] > 0;
}

function obtenerMedicoConEspecialidades(int $idMedico): ?array
{
    $medico = BaseDatos::fila(
        "SELECT m.id_medico, m.registro_profesional, m.nombres, m.apellidos, m.telefono, m.correo, m.id_usuario, m.estado
         FROM medico m
         WHERE m.id_medico = :id",
        [':id' => $idMedico]
    );

    if (!$medico) {
        return null;
    }

    $especialidades = BaseDatos::consultar(
        "SELECT me.id_especialidad, e.nombre FROM medico_especialidad me
         JOIN especialidad e ON e.id_especialidad = me.id_especialidad
         WHERE me.id_medico = :id ORDER BY e.nombre",
        [':id' => $idMedico]
    );

    $medico['especialidades'] = $especialidades;
    return $medico;
}

function medicoInsertar(string $registroProf, string $nombres, string $apellidos, ?string $telefono, ?string $correo): int
{
    $result = BaseDatos::fila(
        "INSERT INTO medico (registro_profesional, nombres, apellidos, telefono, correo, estado)
         VALUES (:registro, :nombres, :apellidos, :telefono, :correo, 'activo')
         RETURNING id_medico",
        [
            ':registro' => $registroProf,
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':telefono' => $telefono,
            ':correo' => $correo,
        ]
    );
    return (int) $result['id_medico'];
}

function medicoAsignarEspecialidad(int $idMedico, int $idEspecialidad): void
{
    BaseDatos::ejecutar(
        "INSERT INTO medico_especialidad (id_medico, id_especialidad)
         VALUES (:medico, :especialidad)
         ON CONFLICT DO NOTHING",
        [':medico' => $idMedico, ':especialidad' => $idEspecialidad]
    );
}

function medicoReemplazarEspecialidades(int $idMedico, array $idsEspecialidades): void
{
    BaseDatos::ejecutar(
        "DELETE FROM medico_especialidad WHERE id_medico = :medico",
        [':medico' => $idMedico]
    );

    foreach ($idsEspecialidades as $idEsp) {
        BaseDatos::ejecutar(
            "INSERT INTO medico_especialidad (id_medico, id_especialidad)
             VALUES (:medico, :especialidad)",
            [':medico' => $idMedico, ':especialidad' => (int) $idEsp]
        );
    }
}

function medicoModificar(int $idMedico, string $nombres, string $apellidos, ?string $telefono, ?string $correo): void
{
    BaseDatos::ejecutar(
        "UPDATE medico SET nombres = :nombres, apellidos = :apellidos, telefono = :telefono, correo = :correo
         WHERE id_medico = :id",
        [
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':id' => $idMedico,
        ]
    );
}

function medicoCambiarEstado(int $idMedico, string $estado): void
{
    BaseDatos::ejecutar(
        "UPDATE medico SET estado = :estado WHERE id_medico = :id",
        [':estado' => $estado, ':id' => $idMedico]
    );
}

function medicoObtenerCitasFuturas(int $idMedico): array
{
    return BaseDatos::consultar(
        "SELECT c.id_cita, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
                p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos, e.nombre AS especialidad
         FROM cita c
         JOIN paciente p ON p.id_paciente = c.id_paciente
         JOIN especialidad e ON e.id_especialidad = c.id_especialidad
         WHERE c.id_medico = :m1 AND c.fecha >= CURRENT_DATE AND c.estado IN (:b1, :b2)
         ORDER BY c.fecha ASC, c.hora_inicio ASC",
        [':m1' => $idMedico, ':b1' => 'Programada', ':b2' => 'Confirmada']
    );
}

function especialidadListarActivas(): array
{
    return BaseDatos::consultar(
        "SELECT id_especialidad, nombre FROM especialidad WHERE estado = 'activo' ORDER BY nombre ASC"
    );
}

function medicoVincularUsuario(int $idMedico, int $idUsuario): void
{
    BaseDatos::ejecutar(
        "UPDATE medico SET id_usuario = :usuario WHERE id_medico = :id",
        [':usuario' => $idUsuario, ':id' => $idMedico]
    );
}

function medicoObtener(int $idMedico): ?array
{
    return BaseDatos::fila(
        "SELECT id_medico, registro_profesional, nombres, apellidos, telefono, correo, estado
         FROM medico WHERE id_medico = :id",
        [':id' => $idMedico]
    );
}
// ---- CU05: fin ----

// ---- CU04: inicio ----
function pacienteObtener(int $idPaciente): ?array
{
    return BaseDatos::fila(
        "SELECT id_paciente, ci, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, estado
         FROM paciente WHERE id_paciente = :id",
        [':id' => $idPaciente]
    );
}
// ---- CU04: fin ----
