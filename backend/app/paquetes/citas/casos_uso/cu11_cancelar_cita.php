<?php
/**
 * CU11 - Cancelar cita
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Alta  ·  Requisito: RF09
 *
 * Cancelar una cita Programada o Confirmada registrando el motivo, sin eliminar físicamente el
 * registro y liberando el horario.
 *
 * Actores ........: Recepcionista, Paciente
 * Permiso ........: cita.programar
 * Tablas .........: cita, historial_estado_cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01). La cita existe en estado Programada o Confirmada.
 *
 * Flujo principal:
 *     1. El usuario busca la cita por código o paciente.
 *     2. El sistema muestra los datos y verifica que el estado sea «Programada» o «Confirmada».
 *     3. El usuario selecciona «Cancelar» e ingresa el motivo de cancelación.
 *     4. El sistema solicita confirmación.
 *     5. El sistema cambia el estado a «Cancelada», guarda el motivo y registra el cambio en el historial.
 *     6. El intervalo queda nuevamente disponible y la cita permanece en las consultas históricas.
 *
 * Excepciones / reglas que debe controlar:
 *     - Cita Atendida o con Inasistencia: no puede cancelarse como si nunca hubiera ocurrido.
 *     - Motivo de cancelación vacío.
 *     - El paciente intenta cancelar una cita que no le pertenece.
 *
 * Postcondición:
 *     La cita queda «Cancelada», deja de ocupar el horario (RN09) y se conserva en el historial.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Citas\Repository\{citaObtener, citaCancelar};
use function App\Paquetes\Personas\Repository\{pacientePorUsuario};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu11CancelarCita
{
    /** PATCH /api/v1/citas/{id}/cancelar — Cancelar cita con motivo */
    public static function cancelar(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $motivo = trim($body['motivo_cancelacion'] ?? '');

        if (!$idCita) throw new ErrorApi(400, 'ID de cita requerido.');
        if (!$motivo) throw new ErrorApi(400, 'Motivo de cancelación requerido.');

        $cita = citaObtener($idCita);
        if (!$cita) throw new ErrorApi(404, 'Cita no encontrada.');
        if ($cita['estado'] !== 'Programada' && $cita['estado'] !== 'Confirmada') {
            throw new ErrorApi(400, 'Solo citas Programada o Confirmada pueden cancelarse.');
        }

        $s = Sesion::usuario();
        if (!$s['permisos'] || !in_array('cita.programar', $s['permisos'])) {
            if ($s['rol'] !== 'Paciente') {
                throw new ErrorApi(403, 'No tienes permiso para cancelar citas.');
            }
            $paciente = pacientePorUsuario($s['id_usuario']);
            if (!$paciente || $paciente['id_paciente'] !== $cita['id_paciente']) {
                throw new ErrorApi(403, 'No puedes cancelar una cita que no es tuya.');
            }
        }

        $ip = ipCliente();

        try {
            BaseDatos::transaccion(function () use ($idCita, $motivo, $s) {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                citaCancelar($idCita, $motivo);
            }, $s['id_usuario']);

            registrarBitacora(
                $s['id_usuario'],
                'CITA_CANCELAR',
                $idCita,
                "Cita {$cita['codigo']} cancelada. Motivo: {$motivo}",
                'cita',
                $ip
            );

            return [
                'mensaje' => 'Cita cancelada correctamente.',
                'id_cita' => $idCita,
            ];
        } catch (\PDOException $e) {
            throw $e;
        }
    }
}
