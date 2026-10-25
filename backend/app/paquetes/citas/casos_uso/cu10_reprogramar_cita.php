<?php
/**
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

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu10ReprogramarCita
{
    /** PATCH /api/v1/citas/{id}/reprogramar — Reprogramar cita */
    public static function reprogramar(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');
        throw new NoImplementado('CU10');
    }
}
