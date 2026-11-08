<?php
/**
 * Paquete reportes — acceso a datos (consultas SQL parametrizadas con PDO).
 * Solo este archivo escribe SQL del paquete. Usa App\Nucleo\BaseDatos::consultar(), fila(), ejecutar() y transaccion().
 *
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Reportes\Repository;

use App\Nucleo\BaseDatos;

/** Reporte por estados de cita */
function reporteEstados(string $desde, string $hasta, ?int $idMedico = null, ?int $idEspecialidad = null): array
{
    $sql = 'SELECT c.estado, COUNT(*) as total FROM cita c WHERE c.fecha BETWEEN :desde AND :hasta';
    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($idMedico) {
        $sql .= ' AND c.id_medico = :m';
        $params[':m'] = $idMedico;
    }
    if ($idEspecialidad) {
        $sql .= ' AND c.id_especialidad = :e';
        $params[':e'] = $idEspecialidad;
    }

    $sql .= ' GROUP BY c.estado ORDER BY c.estado';
    return BaseDatos::consultar($sql, $params);
}

/** Reporte por especialidades */
function reporteEspecialidades(string $desde, string $hasta, ?int $idMedico = null): array
{
    $sql = 'SELECT e.nombre, COUNT(*) as total,
            SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) as atendidas,
            SUM(CASE WHEN c.estado = \'Cancelada\' THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN c.estado = \'Inasistencia\' THEN 1 ELSE 0 END) as inasistencias
            FROM cita c
            JOIN especialidad e ON c.id_especialidad = e.id_especialidad
            WHERE c.fecha BETWEEN :desde AND :hasta';
    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($idMedico) {
        $sql .= ' AND c.id_medico = :m';
        $params[':m'] = $idMedico;
    }

    $sql .= ' GROUP BY e.nombre ORDER BY total DESC';
    return BaseDatos::consultar($sql, $params);
}

/** Reporte por médicos */
function reporteMedicos(string $desde, string $hasta, ?int $idEspecialidad = null): array
{
    $sql = 'SELECT m.nombres || \' \' || m.apellidos as medico, COUNT(*) as total,
            SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) as atendidas,
            SUM(CASE WHEN c.estado = \'Cancelada\' THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN c.estado = \'Inasistencia\' THEN 1 ELSE 0 END) as inasistencias
            FROM cita c
            JOIN medico m ON c.id_medico = m.id_medico
            WHERE c.fecha BETWEEN :desde AND :hasta';
    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($idEspecialidad) {
        $sql .= ' AND c.id_especialidad = :e';
        $params[':e'] = $idEspecialidad;
    }

    $sql .= ' GROUP BY m.id_medico, m.nombres, m.apellidos ORDER BY total DESC';
    return BaseDatos::consultar($sql, $params);
}

/** Reporte de asistencia (agrupado por médico) */
function reporteAsistencia(string $desde, string $hasta, ?int $idMedico = null, ?int $idEspecialidad = null): array
{
    $sql = 'SELECT m.nombres || \' \' || m.apellidos as medico, COUNT(*) as total,
            SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) as atendidas,
            SUM(CASE WHEN c.estado = \'Inasistencia\' THEN 1 ELSE 0 END) as inasistencias,
            CASE
                WHEN COUNT(*) = 0 THEN 0
                WHEN SUM(CASE WHEN c.estado = \'Inasistencia\' THEN 1 ELSE 0 END) + SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) = 0 THEN 0
                ELSE ROUND(100.0 * SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) /
                    (SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) + SUM(CASE WHEN c.estado = \'Inasistencia\' THEN 1 ELSE 0 END)), 1)
            END as porcentaje_asistencia
            FROM cita c
            JOIN medico m ON c.id_medico = m.id_medico
            WHERE c.fecha BETWEEN :desde AND :hasta';
    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($idMedico) {
        $sql .= ' AND c.id_medico = :m';
        $params[':m'] = $idMedico;
    }
    if ($idEspecialidad) {
        $sql .= ' AND c.id_especialidad = :e';
        $params[':e'] = $idEspecialidad;
    }

    $sql .= ' GROUP BY m.id_medico, m.nombres, m.apellidos ORDER BY m.apellidos, m.nombres';
    return BaseDatos::consultar($sql, $params);
}

/** Reporte de ocupación (citas programadas por fecha y médico) */
function reporteOcupacion(string $desde, string $hasta, ?int $idMedico = null, ?int $idEspecialidad = null): array
{
    $sql = 'SELECT c.fecha, m.nombres || \' \' || m.apellidos as medico, COUNT(*) as total,
            SUM(CASE WHEN c.estado = \'Programada\' OR c.estado = \'Confirmada\' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN c.estado = \'Atendida\' THEN 1 ELSE 0 END) as atendidas
            FROM cita c
            JOIN medico m ON c.id_medico = m.id_medico
            WHERE c.fecha BETWEEN :desde AND :hasta AND c.estado <> \'Cancelada\'';
    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($idMedico) {
        $sql .= ' AND c.id_medico = :m';
        $params[':m'] = $idMedico;
    }
    if ($idEspecialidad) {
        $sql .= ' AND c.id_especialidad = :e';
        $params[':e'] = $idEspecialidad;
    }

    $sql .= ' GROUP BY c.fecha, m.id_medico, m.nombres, m.apellidos ORDER BY c.fecha DESC';
    return BaseDatos::consultar($sql, $params);
}
