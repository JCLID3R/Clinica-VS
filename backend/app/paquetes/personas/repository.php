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
