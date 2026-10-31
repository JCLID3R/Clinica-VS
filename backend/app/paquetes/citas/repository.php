<?php
/**
 * Paquete citas — acceso a datos (consultas SQL parametrizadas con PDO).
 * Solo este archivo escribe SQL del paquete. Usa App\Nucleo\BaseDatos::consultar(), fila(), ejecutar() y transaccion().
 *
 * Estado: IMPLEMENTADO (CU09).
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\Repository;

use App\Nucleo\BaseDatos;

// ---- CU09: Programar cita ----

/** Insertar una nueva cita */
function citaInsertar(int $idPaciente, int $idMedico, int $idEspecialidad, string $fecha, string $horaInicio, string $horaFin, string $modalidad, ?string $motivo, int $idUsuario): int
{
    $sql = '
        INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, modalidad, motivo, id_usuario_registro, estado)
        VALUES (:p, :m, :e, :f, :hi, :hf, :mod, :mot, :iu, :est)
        RETURNING id_cita
    ';
    $resultado = BaseDatos::fila($sql, [
        ':p' => $idPaciente,
        ':m' => $idMedico,
        ':e' => $idEspecialidad,
        ':f' => $fecha,
        ':hi' => $horaInicio,
        ':hf' => $horaFin,
        ':mod' => $modalidad,
        ':mot' => $motivo,
        ':iu' => $idUsuario,
        ':est' => 'Programada',
    ]);
    return (int) $resultado['id_cita'];
}

/** Obtener cita por ID */
function citaObtener(int $idCita): ?array
{
    $sql = '
        SELECT c.*, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos, p.ci,
               m.nombres AS medico_nombres, m.apellidos AS medico_apellidos, m.registro_profesional,
               e.nombre AS especialidad_nombre
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id_paciente
        JOIN medico m ON c.id_medico = m.id_medico
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.id_cita = :id
    ';
    return BaseDatos::fila($sql, [':id' => $idCita]);
}
