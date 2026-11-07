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
        SELECT c.*, \'CM-\' || lpad(c.id_cita::text, 4, \'0\') AS codigo,
               p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos, p.ci,
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

// ---- CU10: Reprogramar cita ----

/** Buscar citas con filtros (CU10, CU11) */
function citaBuscar(?string $buscar = null, ?string $fecha = null, ?string $estado = null): array
{
    $sql = '
        SELECT c.id_cita, \'CM-\' || lpad(c.id_cita::text, 4, \'0\') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
               p.id_paciente, p.nombres || \' \' || p.apellidos AS paciente, p.ci,
               m.id_medico, m.nombres || \' \' || m.apellidos AS medico,
               e.nombre AS especialidad
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id_paciente
        JOIN medico m ON c.id_medico = m.id_medico
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE 1=1
    ';
    $params = [];

    if ($buscar) {
        $search = translate(lower($buscar), 'áéíóúüñ', 'aeiouun');
        $sql .= ' AND (translate(lower(p.ci::text), \'áéíóúüñ\', \'aeiouun\') LIKE :buscar OR translate(lower(p.nombres || \' \' || p.apellidos), \'áéíóúüñ\', \'aeiouun\') LIKE :buscar)';
        $params[':buscar'] = '%' . $search . '%';
    }
    if ($fecha) {
        $sql .= ' AND c.fecha = :fecha';
        $params[':fecha'] = $fecha;
    }
    if ($estado) {
        $sql .= ' AND c.estado = :estado';
        $params[':estado'] = $estado;
    }

    $sql .= ' ORDER BY c.fecha DESC, c.hora_inicio DESC';
    return BaseDatos::consultar($sql, $params);
}

/** Reprogramar cita (cambiar fecha/hora/médico) */
function citaReprogramar(int $idCita, string $fecha, string $horaInicio, string $horaFin, int $idMedico): void
{
    $sql = '
        UPDATE cita
        SET fecha = :f, hora_inicio = :hi, hora_fin = :hf, id_medico = :m, fecha_actualizacion = now()
        WHERE id_cita = :id
    ';
    BaseDatos::ejecutar($sql, [
        ':f' => $fecha,
        ':hi' => $horaInicio,
        ':hf' => $horaFin,
        ':m' => $idMedico,
        ':id' => $idCita,
    ]);
}

// ---- CU11: Cancelar cita ----

/** Cancelar cita (cambiar estado y guardar motivo) */
function citaCancelar(int $idCita, string $motivo): void
{
    $sql = '
        UPDATE cita
        SET estado = :est, motivo_cancelacion = :mot, fecha_actualizacion = now()
        WHERE id_cita = :id
    ';
    BaseDatos::ejecutar($sql, [
        ':est' => 'Cancelada',
        ':mot' => $motivo,
        ':id' => $idCita,
    ]);
}

// ---- CU12: Registrar asistencia ----

/** Citas del día (filtrable por médico y estado) */
function citasDelDia(string $fecha, ?int $idMedico = null, ?string $estado = null): array
{
    $sql = '
        SELECT c.id_cita, \'CM-\' || lpad(c.id_cita::text, 4, \'0\') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
               p.id_paciente, p.nombres || \' \' || p.apellidos AS paciente, p.ci,
               m.id_medico, m.nombres || \' \' || m.apellidos AS medico,
               e.nombre AS especialidad
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id_paciente
        JOIN medico m ON c.id_medico = m.id_medico
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.fecha = :f AND c.estado <> \'Cancelada\'
    ';
    $params = [':f' => $fecha];

    if ($idMedico) {
        $sql .= ' AND c.id_medico = :m';
        $params[':m'] = $idMedico;
    }
    if ($estado) {
        $sql .= ' AND c.estado = :est';
        $params[':est'] = $estado;
    }

    $sql .= ' ORDER BY c.hora_inicio';
    return BaseDatos::consultar($sql, $params);
}

/** Acciones válidas para una cita según su estado y hora */
function citaAccionesValidas(int $idCita): array
{
    $cita = citaObtener($idCita);
    if (!$cita) return [];

    $ahora = new \DateTime();
    $citaTime = new \DateTime($cita['fecha'] . ' ' . $cita['hora_inicio']);
    $yaOcurrio = $ahora > $citaTime;

    $acciones = [];
    if ($cita['estado'] === 'Programada') {
        $acciones[] = 'Confirmar';
        $acciones[] = 'Cancelar';
        if ($yaOcurrio) $acciones[] = 'Atendida';
        if ($yaOcurrio) $acciones[] = 'Inasistencia';
    } elseif ($cita['estado'] === 'Confirmada') {
        $acciones[] = 'Cancelar';
        if ($yaOcurrio) $acciones[] = 'Atendida';
        if ($yaOcurrio) $acciones[] = 'Inasistencia';
    }
    return $acciones;
}

/** Cambiar estado de cita */
function citaCambiarEstado(int $idCita, string $estado): void
{
    $sql = '
        UPDATE cita
        SET estado = :est, fecha_actualizacion = now()
        WHERE id_cita = :id
    ';
    BaseDatos::ejecutar($sql, [
        ':est' => $estado,
        ':id' => $idCita,
    ]);
}

/** Citas Programada/Confirmada de días anteriores sin marcar */
function citasPendientesAnteriores(): array
{
    $sql = '
        SELECT c.id_cita, \'CM-\' || lpad(c.id_cita::text, 4, \'0\') AS codigo, c.fecha, c.hora_inicio,
               p.nombres || \' \' || p.apellidos AS paciente, m.nombres || \' \' || m.apellidos AS medico,
               e.nombre AS especialidad
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id_paciente
        JOIN medico m ON c.id_medico = m.id_medico
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.estado IN (\'Programada\', \'Confirmada\')
          AND c.fecha < CURRENT_DATE
        ORDER BY c.fecha DESC, c.hora_inicio DESC
    ';
    return BaseDatos::consultar($sql);
}

// ---- CU13: Consultar agenda médica ----

/** Citas de un médico en un rango de fechas */
function agendaPorMedico(int $idMedico, string $desde, string $hasta): array
{
    $sql = '
        SELECT c.id_cita, \'CM-\' || lpad(c.id_cita::text, 4, \'0\') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
               p.nombres || \' \' || p.apellidos AS paciente, p.ci,
               e.nombre AS especialidad
        FROM cita c
        JOIN paciente p ON c.id_paciente = p.id_paciente
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.id_medico = :m AND c.fecha BETWEEN :desde AND :hasta AND c.estado <> \'Cancelada\'
        ORDER BY c.fecha, c.hora_inicio
    ';
    return BaseDatos::consultar($sql, [':m' => $idMedico, ':desde' => $desde, ':hasta' => $hasta]);
}

/** Bloqueos de agenda en un rango */
function bloqueosEnRango(string $desde, string $hasta, ?int $idMedico = null): array
{
    $sql = 'SELECT id_bloqueo, id_medico, fecha, motivo FROM bloqueo_agenda WHERE fecha BETWEEN :desde AND :hasta';
    $params = [':desde' => $desde, ':hasta' => $hasta];
    if ($idMedico) {
        $sql .= ' AND (id_medico IS NULL OR id_medico = :m)';
        $params[':m'] = $idMedico;
    }
    $sql .= ' ORDER BY fecha';
    return BaseDatos::consultar($sql, $params);
}

// ---- CU14: Historial del paciente ----

/** Citas de un paciente */
function citasPorPaciente(int $idPaciente, ?string $estado = null, ?string $desde = null, ?string $hasta = null): array
{
    $sql = '
        SELECT c.id_cita, \'CM-\' || lpad(c.id_cita::text, 4, \'0\') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
               m.nombres || \' \' || m.apellidos AS medico, e.nombre AS especialidad
        FROM cita c
        JOIN medico m ON c.id_medico = m.id_medico
        JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.id_paciente = :p
    ';
    $params = [':p' => $idPaciente];

    if ($estado) {
        $sql .= ' AND c.estado = :est';
        $params[':est'] = $estado;
    }
    if ($desde && $hasta) {
        $sql .= ' AND c.fecha BETWEEN :desde AND :hasta';
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;
    }

    $sql .= ' ORDER BY c.fecha DESC, c.hora_inicio DESC';
    return BaseDatos::consultar($sql, $params);
}

/** Historial de cambios de una cita */
function historialPorCita(int $idCita): array
{
    $sql = '
        SELECT h.estado_anterior, h.estado_nuevo, h.fecha_anterior, h.hora_anterior, h.observacion, h.fecha,
               u.username AS usuario
        FROM historial_estado_cita h
        LEFT JOIN usuario u ON h.id_usuario = u.id_usuario
        WHERE h.id_cita = :id
        ORDER BY h.fecha DESC
    ';
    return BaseDatos::consultar($sql, [':id' => $idCita]);
}

// ---- CU13: Médico por usuario ----

/** Obtener médico asociado a un usuario */
function medicoPorUsuario(int $idUsuario): ?array
{
    $sql = 'SELECT * FROM medico WHERE id_usuario = :id';
    return BaseDatos::fila($sql, [':id' => $idUsuario]);
}
