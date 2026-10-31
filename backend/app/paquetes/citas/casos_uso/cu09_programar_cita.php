<?php
/**
 * CU09 - Programar cita médica
 * Paquete: citas  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF06 / RF07
 *
 * Registrar una cita para un paciente con un médico de una especialidad en una fecha y hora
 * disponibles, impidiendo citas duplicadas o superpuestas.
 *
 * Actores ........: Recepcionista, Paciente (Ciclo 2)
 * Permiso ........: cita.programar
 * Tablas .........: paciente, medico, horario_atencion, cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01). El paciente está registrado y activo (CU04) y el médico tiene horario (CU07).
 *
 * Flujo principal:
 *     1. El usuario ingresa a «Programar cita».
 *     2. Busca y selecciona al paciente por CI o nombre (CU04).
 *     3. Selecciona la especialidad, el médico y la fecha; el sistema muestra solo los horarios disponibles (CU08).
 *     4. Selecciona la hora disponible, la modalidad e ingresa un motivo breve (opcional).
 *     5. Al guardar, el sistema inicia una transacción y vuelve a verificar en el servidor que el paciente y el médico estén activos, que la hora esté dentro del horario de atención, que no sea una fecha pasada y que no exista otra cita superpuesta.
 *     6. El sistema genera el código único (CM-0001), registra la fecha de creación y el estado «Programada», y confirma la transacción.
 *     7. El sistema muestra el comprobante de la cita (código, paciente, especialidad, médico, fecha, horario y motivo).
 *
 * Excepciones / reglas que debe controlar:
 *     - Otro usuario ocupó el horario mientras se completaba el formulario: el sistema rechaza la operación y muestra «El horario ya no está disponible» (RN07, RN13).
 *     - Hora fuera del horario de atención o fecha pasada (RN05, RN06).
 *     - Médico o paciente inactivo (RN04).
 *
 * Postcondición:
 *     La cita queda registrada en estado «Programada» y el intervalo deja de estar disponible.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Citas\Repository\{citaInsertar, citaObtener};
use function App\Paquetes\Personas\Repository\{pacienteObtener, medicoObtener};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu09ProgramarCita
{
    /** POST /api/v1/citas — Programar cita */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');

        $body = $p->cuerpo();
        $idPaciente = (int) ($body['id_paciente'] ?? 0);
        $idMedico = (int) ($body['id_medico'] ?? 0);
        $idEspecialidad = (int) ($body['id_especialidad'] ?? 0);
        $fecha = trim($body['fecha'] ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFin = trim($body['hora_fin'] ?? '');
        $modalidad = trim($body['modalidad'] ?? 'presencial');
        $motivo = trim($body['motivo'] ?? '') ?: null;

        $errores = [];
        if (!$idPaciente) {
            $errores['id_paciente'] = 'Paciente requerido.';
        }
        if (!$idMedico) {
            $errores['id_medico'] = 'Médico requerido.';
        }
        if (!$idEspecialidad) {
            $errores['id_especialidad'] = 'Especialidad requerida.';
        }
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $errores['fecha'] = 'Fecha requerida en formato YYYY-MM-DD.';
        }
        if (!$horaInicio || !preg_match('/^\d{2}:\d{2}$/', $horaInicio)) {
            $errores['hora_inicio'] = 'Formato debe ser HH:MM.';
        }
        if (!$horaFin || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
            $errores['hora_fin'] = 'Formato debe ser HH:MM.';
        }
        if ($horaFin <= $horaInicio) {
            $errores['hora_fin'] = 'La hora de fin debe ser posterior a la hora de inicio.';
        }
        if (!in_array($modalidad, ['presencial', 'virtual'])) {
            $errores['modalidad'] = 'Modalidad debe ser presencial o virtual.';
        }

        if ($errores) {
            throw new ErrorApi(400, 'Errores en el formulario.', $errores);
        }

        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            throw new ErrorApi(400, 'No se puede programar citas en fechas pasadas.');
        }

        $paciente = pacienteObtener($idPaciente);
        if (!$paciente || $paciente['estado'] !== 'activo') {
            throw new ErrorApi(404, 'Paciente no encontrado o inactivo.');
        }

        $medico = medicoObtener($idMedico);
        if (!$medico || $medico['estado'] !== 'activo') {
            throw new ErrorApi(404, 'Médico no encontrado o inactivo.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        try {
            $idCita = BaseDatos::transaccion(function () use ($idPaciente, $idMedico, $idEspecialidad, $fecha, $horaInicio, $horaFin, $modalidad, $motivo, $s): int {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);

                return citaInsertar($idPaciente, $idMedico, $idEspecialidad, $fecha, $horaInicio, $horaFin, $modalidad, $motivo, $s['id_usuario']);
            }, $s['id_usuario']);

            $cita = citaObtener($idCita);
            $codigo = 'CM-' . str_pad((string) $idCita, 4, '0', STR_PAD_LEFT);

            registrarBitacora(
                $s['id_usuario'],
                'CITA_CREAR',
                $idCita,
                "Cita programada: {$cita['paciente_nombres']} {$cita['paciente_apellidos']} con {$cita['medico_nombres']} {$cita['medico_apellidos']} el {$fecha} a las {$horaInicio}",
                'cita',
                $ip
            );

            return [
                'mensaje' => 'Cita programada correctamente.',
                'id_cita' => $idCita,
                'codigo' => $codigo,
                'comprobante' => [
                    'codigo' => $codigo,
                    'paciente' => "{$cita['paciente_nombres']} {$cita['paciente_apellidos']}",
                    'especialidad' => $cita['especialidad_nombre'],
                    'medico' => "{$cita['medico_nombres']} {$cita['medico_apellidos']}",
                    'fecha' => $fecha,
                    'horario' => "{$horaInicio} - {$horaFin}",
                    'modalidad' => $modalidad,
                    'motivo' => $motivo,
                ],
            ];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario ya no está disponible. Otro usuario ocupó este espacio.');
            }
            throw $e;
        }
    }
}
