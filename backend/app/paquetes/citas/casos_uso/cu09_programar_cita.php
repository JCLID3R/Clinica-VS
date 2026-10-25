<?php
/**
 * CU09 - Programar cita médica
 * Paquete: citas  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF06 / RF07
 *
 * Registrar una cita para un paciente con un médico de una especialidad en una fecha y hora
 * disponibles, impidiendo citas duplicadas o superpuestas.
 *
 * Actores ........: Recepcionista
 * Permiso ........: cita.programar
 * Tablas .........: paciente, medico, horario_atencion, cita
 *
 * Precondición:
 *     La recepcionista inició sesión (CU01). El paciente está registrado y activo (CU04) y el médico tiene horario (CU07).
 *
 * Flujo principal:
 *     1. La recepcionista ingresa a «Programar cita».
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
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu09ProgramarCita
{
    /** POST /api/v1/citas — Programar cita */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');
        throw new NoImplementado('CU09');
    }
}
