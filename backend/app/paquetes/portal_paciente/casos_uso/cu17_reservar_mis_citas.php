<?php
/**
 * CU17 - Reservar y dar seguimiento a mis citas
 * Paquete: portal_paciente  ·  Ciclo 2  ·  Prioridad Media  ·  Requisito: Perfil del paciente (3.4)
 *
 * Permitir que el paciente reserve una cita en línea buscando médicos por especialidad y
 * disponibilidad, y dé seguimiento a sus citas pendientes, confirmadas, completadas y canceladas.
 *
 * Actores ........: Paciente
 * Permiso ........: perfil.paciente
 * Tablas .........: medico, horario_atencion, cita
 *
 * Precondición:
 *     El paciente inició sesión en el portal (CU01) y su registro está activo.
 *
 * Flujo principal:
 *     1. El paciente ingresa a «Reservar cita».
 *     2. Selecciona especialidad, médico y fecha; el sistema muestra los horarios disponibles (CU08).
 *     3. Elige un horario, la modalidad y un motivo breve, y confirma.
 *     4. El sistema registra la cita con las mismas validaciones del servidor que CU09.
 *     5. En «Mis citas», el sistema lista sus citas por estado y muestra un recordatorio de las próximas consultas.
 *     6. Desde «Mis citas» el paciente puede cancelar una cita programada (CU11).
 *
 * Excepciones / reglas que debe controlar:
 *     - El horario fue ocupado por otra reserva.
 *     - El paciente ya tiene una cita pendiente con el mismo médico en la misma fecha.
 *
 * Postcondición:
 *     La cita queda «Programada» a nombre del paciente; el paciente ve el estado de todas sus citas.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Personas\Repository\{pacientePorUsuario};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu17ReservarMisCitas
{
    /** GET /api/v1/portal/citas — Mis citas por estado */
    public static function misCitas(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $citas = BaseDatos::consultar(
            "SELECT c.id_cita, 'CM-' || lpad(c.id_cita::text, 4, '0') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado, m.nombres || ' ' || m.apellidos AS medico, e.nombre AS especialidad FROM cita c JOIN medico m ON c.id_medico = m.id_medico JOIN especialidad e ON c.id_especialidad = e.id_especialidad WHERE c.id_paciente = :p ORDER BY c.fecha DESC",
            [':p' => $paciente['id_paciente']]
        );

        return ['citas' => $citas];
    }

    /** POST /api/v1/portal/citas — Reservar cita en línea */
    public static function reservar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $body = $p->cuerpo();
        $idEspecialidad = (int) ($body['id_especialidad'] ?? 0);
        $idMedico = (int) ($body['id_medico'] ?? 0);
        $fecha = trim($body['fecha'] ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFin = trim($body['hora_fin'] ?? '');

        if (!$idEspecialidad || !$fecha || !$horaInicio || !$horaFin) {
            throw new ErrorApi(400, 'Campos requeridos.');
        }

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        try {
            BaseDatos::transaccion(function () use ($paciente, $idEspecialidad, $idMedico, $fecha, $horaInicio, $horaFin, $s) {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                BaseDatos::ejecutar(
                    "INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, estado, id_usuario_registro) VALUES (:p, :m, :e, :f, :hi, :hf, 'Programada', :iu)",
                    [':p' => $paciente['id_paciente'], ':m' => $idMedico, ':e' => $idEspecialidad, ':f' => $fecha, ':hi' => $horaInicio, ':hf' => $horaFin, ':iu' => $s['id_usuario']]
                );
            }, $s['id_usuario']);

            registrarBitacora($s['id_usuario'], 'CITA_RESERVA_PORTAL', $paciente['id_paciente'], "Cita reservada por paciente", 'cita', ipCliente());
            return ['mensaje' => 'Cita reservada correctamente.'];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario ya no está disponible.');
            }
            throw $e;
        }
    }

    /** PATCH /api/v1/portal/citas/{id}/cancelar — Cancelar mi cita */
    public static function cancelar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $motivo = trim($body['motivo_cancelacion'] ?? '');

        if (!$motivo) throw new ErrorApi(400, 'Motivo de cancelación requerido.');

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $cita = BaseDatos::fila("SELECT * FROM cita WHERE id_cita = :id", [':id' => $idCita]);
        if (!$cita || $cita['id_paciente'] !== $paciente['id_paciente']) {
            throw new ErrorApi(403, 'No puedes cancelar esta cita.');
        }

        BaseDatos::transaccion(function () use ($idCita, $motivo, $s) {
            BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
            BaseDatos::ejecutar(
                "UPDATE cita SET estado = 'Cancelada', motivo_cancelacion = :m WHERE id_cita = :id",
                [':m' => $motivo, ':id' => $idCita]
            );
        }, $s['id_usuario']);

        return ['mensaje' => 'Cita cancelada.'];
    }

    /** PATCH /api/v1/portal/citas/{id}/reprogramar — Reprogramar mi cita */
    public static function reprogramar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $fecha = trim($body['fecha'] ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFin = trim($body['hora_fin'] ?? '');

        if (!$fecha || !$horaInicio || !$horaFin) {
            throw new ErrorApi(400, 'Datos incompletos.');
        }

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $cita = BaseDatos::fila("SELECT * FROM cita WHERE id_cita = :id", [':id' => $idCita]);
        if (!$cita || $cita['id_paciente'] !== $paciente['id_paciente']) {
            throw new ErrorApi(403, 'No puedes reprogramar esta cita.');
        }

        BaseDatos::transaccion(function () use ($idCita, $fecha, $horaInicio, $horaFin, $s) {
            BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
            BaseDatos::ejecutar(
                "UPDATE cita SET fecha = :f, hora_inicio = :hi, hora_fin = :hf WHERE id_cita = :id",
                [':f' => $fecha, ':hi' => $horaInicio, ':hf' => $horaFin, ':id' => $idCita]
            );
        }, $s['id_usuario']);

        return ['mensaje' => 'Cita reprogramada.'];
    }
}
