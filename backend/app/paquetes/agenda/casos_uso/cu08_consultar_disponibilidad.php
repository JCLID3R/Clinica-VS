<?php
/**
 * CU08 - Consultar disponibilidad de citas
 * Paquete: agenda  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF05 / RF06
 *
 * Mostrar los horarios libres de un médico en una fecha, calculados a partir de su horario de
 * atención, la duración configurada y las citas ya ocupadas.
 *
 * Actores ........: Recepcionista, Paciente
 * Permiso ........: agenda.consultar
 * Tablas .........: especialidad, medico_especialidad, medico, horario_atencion, cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01). Existen médicos con horarios registrados (CU07).
 *
 * Flujo principal:
 *     1. El usuario selecciona una especialidad.
 *     2. El sistema lista los médicos activos de esa especialidad.
 *     3. El usuario selecciona un médico y una fecha (no pasada).
 *     4. El sistema obtiene el horario de atención del día de la semana, divide el bloque en intervalos según la duración configurada y descarta los intervalos pasados, bloqueados u ocupados por citas no canceladas.
 *     5. El sistema muestra únicamente los horarios disponibles.
 *
 * Excepciones / reglas que debe controlar:
 *     - Fecha pasada: el sistema no la permite.
 *     - El médico no atiende ese día o la fecha está bloqueada: se informa «Sin horarios disponibles».
 *     - Todos los intervalos están ocupados.
 *
 * Postcondición:
 *     El usuario conoce los horarios disponibles para programar una cita (CU09 / CU17).
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu08ConsultarDisponibilidad
{
    /** GET /api/v1/agenda/especialidades/{id}/medicos — Médicos activos de una especialidad */
    public static function medicosPorEspecialidad(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');
        throw new NoImplementado('CU08');
    }
    /** GET /api/v1/agenda/disponibilidad — Horarios libres (?id_medico=&fecha=) */
    public static function horariosDisponibles(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');
        throw new NoImplementado('CU08');
    }
}
