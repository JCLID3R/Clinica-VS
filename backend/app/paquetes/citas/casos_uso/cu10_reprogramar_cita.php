<?php
/**
 * CU10 - Reprogramar cita
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Alta  ·  Requisito: RF09
 *
 * Buscar una cita en estado Programada o Confirmada y cambiar su fecha, hora o médico,
 * respetando disponibilidad y validaciones. Libera la ficha anterior.
 *
 * Actores ........: Recepcionista
 * Permiso ........: cita.programar
 * Tablas .........: cita, historial_estado_cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01). La cita existe en estado Programada o Confirmada.
 *
 * Flujo principal:
 *     1. El usuario busca la cita por código o paciente.
 *     2. Sistema muestra datos actuales.
 *     3. Usuario selecciona nueva fecha, médico y ficha.
 *     4. Sistema valida disponibilidad y bloqueos.
 *     5. Al confirmar, cambia fecha/hora/médico y registra cambio en historial.
 *     6. Ficha anterior queda libre, nueva queda ocupada.
 *
 * Excepciones / reglas que debe controlar:
 *     - Cita Atendida o Cancelada: no puede reprogramarse.
 *     - Horario no disponible o superposición (RN07, RN13).
 *     - Bloqueo de agenda (RN06).
 *
 * Postcondición:
 *     Cita con nuevas fecha/hora/médico, registro en historial, cambio en disponibilidad.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Citas\Repository\{citaObtener, citaReprogramar};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu10ReprogramarCita
{
    /** GET /api/v1/citas?buscar=&fecha=&estado= — Buscar citas */
    public static function buscarCitas(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');

        $buscar = $p->param('buscar', '');
        $fecha = $p->param('fecha', '');
        $estado = $p->param('estado', '');

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
            $search = '%' . $buscar . '%';
            $sql .= ' AND (p.ci::text LIKE :buscar OR translate(lower(p.nombres || \' \' || p.apellidos), \'áéíóúüñ\', \'aeiouun\') LIKE translate(lower(:buscar), \'áéíóúüñ\', \'aeiouun\'))';
            $params[':buscar'] = $search;
        }
        if ($fecha) {
            $sql .= ' AND c.fecha = :fecha';
            $params[':fecha'] = $fecha;
        }
        if ($estado) {
            $sql .= ' AND c.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql .= ' AND c.estado IN (\'Programada\', \'Confirmada\') ORDER BY c.fecha DESC, c.hora_inicio DESC';
        $citas = BaseDatos::consultar($sql, $params);

        return ['citas' => $citas];
    }

    /** PATCH /api/v1/citas/{id}/reprogramar — Reprogramar cita */
    public static function reprogramar(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $fecha = trim($body['fecha'] ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFin = trim($body['hora_fin'] ?? '');
        $idMedico = (int) ($body['id_medico'] ?? 0);

        if (!$idCita) throw new ErrorApi(400, 'ID de cita requerido.');
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new ErrorApi(400, 'Fecha requerida en formato YYYY-MM-DD.');
        }
        if (!$horaInicio || !preg_match('/^\d{2}:\d{2}$/', $horaInicio)) {
            throw new ErrorApi(400, 'Hora de inicio en formato HH:MM.');
        }
        if (!$horaFin || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
            throw new ErrorApi(400, 'Hora de fin en formato HH:MM.');
        }
        if ($horaFin <= $horaInicio) {
            throw new ErrorApi(400, 'Hora de fin debe ser posterior a hora de inicio.');
        }
        if (!$idMedico) throw new ErrorApi(400, 'Médico requerido.');

        $cita = citaObtener($idCita);
        if (!$cita) throw new ErrorApi(404, 'Cita no encontrada.');
        if ($cita['estado'] !== 'Programada' && $cita['estado'] !== 'Confirmada') {
            throw new ErrorApi(400, 'Solo citas Programada o Confirmada pueden reprogramarse.');
        }

        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            throw new ErrorApi(400, 'No se puede programar en fechas pasadas.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        try {
            BaseDatos::transaccion(function () use ($idCita, $idMedico, $fecha, $horaInicio, $horaFin, $s) {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                citaReprogramar($idCita, $fecha, $horaInicio, $horaFin, $idMedico);
            }, $s['id_usuario']);

            $citaActualizada = citaObtener($idCita);

            registrarBitacora(
                $s['id_usuario'],
                'CITA_REPROGRAMAR',
                $idCita,
                "Cita {$cita['codigo']} reprogramada de {$cita['fecha']} a {$fecha} {$horaInicio}-{$horaFin}",
                'cita',
                $ip
            );

            return [
                'mensaje' => 'Cita reprogramada correctamente.',
                'cita' => $citaActualizada,
            ];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario ya no está disponible. Conflicto de citas.');
            }
            throw $e;
        }
    }
}
