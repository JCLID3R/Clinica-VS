<?php
/**
 * CU12 - Registrar asistencia de la cita
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Alta  ·  Requisito: RF10
 *
 * Controlar los estados de la cita: confirmar la asistencia, marcarla como Atendida o registrar la
 * Inasistencia del paciente, aplicando solo transiciones válidas.
 *
 * Actores ........: Recepcionista, Médico
 * Permiso ........: cita.asistencia
 * Tablas .........: cita, historial_estado_cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01) y tiene el permiso correspondiente (cita.asistencia o cita.atender).
 *
 * Flujo principal:
 *     1. El usuario abre la agenda del día (CU13) y selecciona una cita.
 *     2. El sistema muestra el estado actual y solo las acciones válidas para ese estado.
 *     3. La recepcionista confirma la llegada del paciente («Confirmada») o registra «Inasistencia» si no se presentó.
 *     4. El médico, si tiene el permiso cita.atender, marca la cita como «Atendida» al finalizar la consulta.
 *     5. El sistema valida la transición, actualiza el estado y registra el cambio en el historial con el usuario y la fecha.
 *
 * Excepciones / reglas que debe controlar:
 *     - Transición no permitida (p. ej. Cancelada → Atendida) sin un procedimiento autorizado.
 *     - Marcar Atendida o Inasistencia una cita cuya hora aún no llegó.
 *     - Médico sin permiso cita.atender.
 *
 * Postcondición:
 *     La cita queda en el nuevo estado y el cambio es trazable.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu12RegistrarAsistencia
{
    /** GET /api/v1/citas/{id}/acciones — Acciones válidas según el estado */
    public static function accionesValidas(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');
        throw new NoImplementado('CU12');
    }
    /** PATCH /api/v1/citas/{id}/estado — Confirmar, atender o registrar inasistencia */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');
        throw new NoImplementado('CU12');
    }
}
