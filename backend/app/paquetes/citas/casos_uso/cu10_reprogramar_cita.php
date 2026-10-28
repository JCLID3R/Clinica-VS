<?php
/**
cu10_reprogramar_cita.php
 * CU10 - Reprogramar cita
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Alta  ·  Requisito: RF08
 *
 * Cambiar la fecha y hora de una cita Programada o Confirmada a un nuevo horario disponible del
 * médico, manteniendo la integridad y la trazabilidad.
 *
 * Actores ........: Recepcionista
 * Permiso ........: cita.programar
 * Tablas .........: cita, horario_atencion, historial_estado_cita
 *
 * Precondición:
 *     La recepcionista inició sesión (CU01). La cita existe en estado Programada o Confirmada.
 *
 * Flujo principal:
 *     1. La recepcionista busca la cita por código o por paciente.
 *     2. El sistema recupera los datos de la cita y verifica que su estado sea «Programada» o «Confirmada».
 *     3. La recepcionista selecciona una nueva fecha; el sistema muestra los horarios disponibles del médico (CU08).
 *     4. La recepcionista elige el nuevo horario y confirma.
 *     5. El sistema, dentro de una transacción, verifica nuevamente la disponibilidad, actualiza la fecha y hora y registra en el historial la fecha y hora anteriores.
 *     6. El sistema muestra el comprobante actualizado.
 *
 * Excepciones / reglas que debe controlar:
 *     - Cita Atendida, Cancelada o con Inasistencia: no se puede reprogramar directamente (política: registrar una nueva cita).
 *     - El nuevo horario fue ocupado por otra cita.
 *     - Nueva fecha pasada o fuera del horario de atención.
 *
 * Postcondición:
 *     La cita queda con la nueva fecha y hora; el horario anterior se libera y el cambio queda en el historial.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{Peticion, Sesion, Respuesta, Validador, Errores};
use App\Paquetes\Citas\{Repository, Politicas};

final class Cu10ReprogramarCita
{
    /**
     * PATCH /api/v1/citas/{id}/reprogramar
     * Body: { "id_horario": 123 }  (o { "fecha": "2026-11-05", "hora": "10:30" })
     */
    public static function reprogramar(Peticion $p): mixed
    {
        // 1. Permiso
        Sesion::requerir('cita.programar');

        // 2. Validar entrada
        $idCita = (int) $p->param('id');
        if ($idCita <= 0) {
            return Respuesta::error('ID de cita inválido', 400);
        }

        $datos = $p->body();
        $idHorario = (int) ($datos['id_horario'] ?? 0);
        if ($idHorario <= 0) {
            return Respuesta::error('Debe seleccionar un nuevo horario disponible', 400);
        }

        // 3. Obtener la cita y validar estado (Programada o Confirmada)
        $cita = Repository::obtenerCitaPorId($idCita);
        if (!$cita) {
            return Respuesta::error('Cita no encontrada', 404);
        }

        $estadosPermitidos = ['Programada', 'Confirmada'];
        if (!in_array($cita['estado'], $estadosPermitidos, true)) {
            return Respuesta::error(
                'Solo se pueden reprogramar citas en estado Programada o Confirmada. ' .
                'Para citas ' . $cita['estado'] . ', registre una nueva cita.',
                409
            );
        }

        // 4. Obtener el nuevo horario y validar que pertenezca al mismo médico
        $nuevoHorario = Repository::obtenerHorarioPorId($idHorario);
        if (!$nuevoHorario) {
            return Respuesta::error('Horario no encontrado', 404);
        }

        if ((int) $nuevoHorario['id_medico'] !== (int) $cita['id_medico']) {
            return Respuesta::error('El nuevo horario no pertenece al médico de la cita', 409);
        }

        // 5. Validar que la nueva fecha/hora no esté en el pasado
        $fechaHoraNueva = $nuevoHorario['fecha'] . ' ' . $nuevoHorario['hora_inicio'];
        if (strtotime($fechaHoraNueva) < time()) {
            return Respuesta::error('No se puede reprogramar a una fecha/hora pasada', 409);
        }

        // 6. Validar disponibilidad (que no haya otra cita en ese horario)
        if (Repository::horarioOcupado($idHorario, $idCita)) {
            return Respuesta::error('El horario seleccionado ya está ocupado por otra cita', 409);
        }

        // 7. Transacción: actualizar cita + registrar historial
        try {
            Repository::iniciarTransaccion();

            $datosAnteriores = [
                'fecha' => $cita['fecha'],
                'hora'  => $cita['hora'],
            ];

            Repository::actualizarCitaHorario($idCita, $idHorario, $datosAnteriores);

            Repository::registrarHistorial(
                $idCita,
                'Reprogramada',
                'Reprogramación: ' . $datosAnteriores['fecha'] . ' ' . $datosAnteriores['hora'] .
                ' → ' . $nuevoHorario['fecha'] . ' ' . $nuevoHorario['hora_inicio']
            );

            Repository::confirmarTransaccion();

            // 8. Devolver comprobante actualizado
            $citaActualizada = Repository::obtenerCitaPorId($idCita);
            return Respuesta::exito([
                'mensaje' => 'Cita reprogramada correctamente',
                'cita'    => $citaActualizada,
            ]);
        } catch (\Throwable $e) {
            Repository::revertirTransaccion();
            return Respuesta::error('Error al reprogramar la cita: ' . $e->getMessage(), 500);
        }
    }
}
