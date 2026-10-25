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
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu11CancelarCita
{
    /** PATCH /api/v1/citas/{id}/cancelar — Cancelar cita con motivo */
    public static function cancelar(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');
        throw new NoImplementado('CU11');
    }
}
