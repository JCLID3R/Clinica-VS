<?php
/**
 * Paquete agenda — validación de los datos que entran y salen de la API.
 * Cada función recibe el cuerpo JSON de la petición y devuelve los datos limpios,
 * o lanza ErrorApi(400) con los errores por campo (usar App\Nucleo\Validador).
 *
 * Estado: IMPLEMENTADO (CU07).
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\Schemas;

use App\Nucleo\ErrorApi;

// ---- CU07: Gestionar horarios de atención ----

function validar_agregar_horario(array $datos): array
{
    $errores = [];

    $idMedico = (int) ($datos['id_medico'] ?? 0);
    if (!$idMedico) {
        $errores['id_medico'] = 'ID del médico requerido.';
    }

    $diaSemana = (int) ($datos['dia_semana'] ?? 0);
    if (!$diaSemana || $diaSemana < 1 || $diaSemana > 7) {
        $errores['dia_semana'] = 'Día debe estar entre 1 (lunes) y 7 (domingo).';
    }

    $horaInicio = trim($datos['hora_inicio'] ?? '');
    if (!$horaInicio || !preg_match('/^\d{2}:\d{2}$/', $horaInicio)) {
        $errores['hora_inicio'] = 'Formato debe ser HH:MM.';
    }

    $horaFin = trim($datos['hora_fin'] ?? '');
    if (!$horaFin || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
        $errores['hora_fin'] = 'Formato debe ser HH:MM.';
    }

    $duracion = (int) ($datos['duracion_cita'] ?? 0);
    if (!in_array($duracion, [15, 20, 30, 45, 60])) {
        $errores['duracion_cita'] = 'Duración debe ser 15, 20, 30, 45 o 60 minutos.';
    }

    if ($horaFin && $horaInicio && $horaFin <= $horaInicio) {
        $errores['hora_fin'] = 'La hora de fin debe ser posterior a la hora de inicio.';
    }

    if ($horaInicio && $horaFin && $duracion) {
        $minInicio = (int) explode(':', $horaInicio)[0] * 60 + (int) explode(':', $horaInicio)[1];
        $minFin = (int) explode(':', $horaFin)[0] * 60 + (int) explode(':', $horaFin)[1];
        $minTotal = $minFin - $minInicio;

        if ($minTotal % $duracion !== 0) {
            $errores['duracion_cita'] = "El rango de horas debe ser divisible por la duración ({$duracion} minutos).";
        }
    }

    if ($errores) {
        throw new ErrorApi(400, 'Errores en el formulario.', $errores);
    }

    return [
        'id_medico' => $idMedico,
        'dia_semana' => $diaSemana,
        'hora_inicio' => $horaInicio,
        'hora_fin' => $horaFin,
        'duracion_cita' => $duracion,
    ];
}

function validar_modificar_horario(array $datos): array
{
    return validar_agregar_horario($datos);
}

function validar_cambiar_estado_horario(array $datos): array
{
    $errores = [];

    $estado = trim($datos['estado'] ?? '');
    if (!$estado || !in_array($estado, ['activo', 'inactivo'])) {
        $errores['estado'] = 'Estado debe ser activo o inactivo.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Errores en el formulario.', $errores);
    }

    return [
        'estado' => $estado,
    ];
}
