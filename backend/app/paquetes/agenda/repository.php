<?php
/**
 * Paquete agenda — acceso a datos (consultas SQL parametrizadas con PDO).
 * Solo este archivo escribe SQL del paquete. Usa App\Nucleo\BaseDatos::consultar(), fila(), ejecutar() y transaccion().
 *
 * Estado: IMPLEMENTADO (CU07).
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\Repository;

use App\Nucleo\BaseDatos;

// ---- CU07: Gestionar horarios de atención ----

/** Obtener todos los bloques semanales de un médico, ordenados por día y hora. */
function horarioListarPorMedico(int $idMedico): array
{
    $sql = '
        SELECT id_horario, id_medico, dia_semana, hora_inicio, hora_fin, duracion_cita, estado
        FROM horario_atencion
        WHERE id_medico = :m
        ORDER BY dia_semana, hora_inicio
    ';
    return BaseDatos::consultar($sql, [':m' => $idMedico]);
}

/** Verificar si existe superposición de horarios (para validación previa). */
function horarioExisteSuperposicion(int $idMedico, int $diaSemana, string $horaInicio, string $horaFin, ?int $idHorarioExcluir = null): bool
{
    $sql = '
        SELECT 1 FROM horario_atencion
        WHERE id_medico = :m
            AND dia_semana = :d
            AND estado = :e
            AND tsrange(DATE \'2000-01-01\' + hora_inicio, DATE \'2000-01-01\' + hora_fin)
                && tsrange(DATE \'2000-01-01\' + :hi::time, DATE \'2000-01-01\' + :hf::time)
    ';
    $params = [':m' => $idMedico, ':d' => $diaSemana, ':e' => 'activo', ':hi' => $horaInicio, ':hf' => $horaFin];

    if ($idHorarioExcluir) {
        $sql .= ' AND id_horario != :eh';
        $params[':eh'] = $idHorarioExcluir;
    }

    return (bool) BaseDatos::fila($sql, $params);
}

/** Insertar un nuevo bloque de horario. */
function horarioInsertar(int $idMedico, int $diaSemana, string $horaInicio, string $horaFin, int $duracionCita): int
{
    $sql = '
        INSERT INTO horario_atencion (id_medico, dia_semana, hora_inicio, hora_fin, duracion_cita, estado)
        VALUES (:m, :d, :hi, :hf, :dc, :e)
        RETURNING id_horario
    ';
    $resultado = BaseDatos::fila($sql, [
        ':m' => $idMedico,
        ':d' => $diaSemana,
        ':hi' => $horaInicio,
        ':hf' => $horaFin,
        ':dc' => $duracionCita,
        ':e' => 'activo',
    ]);
    return (int) $resultado['id_horario'];
}

/** Modificar un bloque de horario existente. */
function horarioModificar(int $idHorario, string $horaInicio, string $horaFin, int $duracionCita): void
{
    $sql = '
        UPDATE horario_atencion
        SET hora_inicio = :hi, hora_fin = :hf, duracion_cita = :dc
        WHERE id_horario = :id
    ';
    BaseDatos::ejecutar($sql, [
        ':id' => $idHorario,
        ':hi' => $horaInicio,
        ':hf' => $horaFin,
        ':dc' => $duracionCita,
    ]);
}

/** Cambiar estado de un bloque (activo/inactivo). */
function horarioCambiarEstado(int $idHorario, string $estado): void
{
    $sql = '
        UPDATE horario_atencion
        SET estado = :e
        WHERE id_horario = :id
    ';
    BaseDatos::ejecutar($sql, [':id' => $idHorario, ':e' => $estado]);
}

/** Obtener un bloque por ID (para validación). */
function horarioObtener(int $idHorario): ?array
{
    $sql = 'SELECT * FROM horario_atencion WHERE id_horario = :id';
    return BaseDatos::fila($sql, [':id' => $idHorario]);
}

/** Obtener citas futuras en el rango de un bloque de horario. */
function horarioObtenerCitasFuturas(int $idMedico, int $diaSemana, string $horaInicio, string $horaFin): array
{
    $sql = '
        SELECT DISTINCT
            c.id_cita, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
            p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos,
            e.nombre AS especialidad
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id_paciente
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.id_medico = :m
            AND c.estado IN (\'Programada\', \'Confirmada\')
            AND c.fecha > CURRENT_DATE
            AND EXTRACT(ISODOW FROM c.fecha) = :d
            AND c.hora_inicio < :hf::time
            AND c.hora_fin > :hi::time
        ORDER BY c.fecha, c.hora_inicio
    ';
    return BaseDatos::consultar($sql, [
        ':m' => $idMedico,
        ':d' => $diaSemana,
        ':hi' => $horaInicio,
        ':hf' => $horaFin,
    ]);
}

/** Registrar evento en bitácora. */
function registrarBitacora(int $idUsuario, string $accion, int $idRegistro, string $detalle, string $tabla, string $ip): void
{
    $sql = '
        INSERT INTO bitacora (id_usuario, accion, id_registro, detalle, tabla, ip)
        VALUES (:iu, :a, :ir, :d, :t, :ip)
    ';
    BaseDatos::ejecutar($sql, [
        ':iu' => $idUsuario,
        ':a' => $accion,
        ':ir' => $idRegistro,
        ':d' => $detalle,
        ':t' => $tabla,
        ':ip' => $ip,
    ]);
}
