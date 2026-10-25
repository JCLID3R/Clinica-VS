<?php
/**
 * CU14 - Consultar historial de citas del paciente
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Media  ·  Requisito: RF12
 *
 * Visualizar todas las citas de un paciente (pasadas, futuras y canceladas) con fecha y hora,
 * médico, especialidad, estado y fecha de registro, sin perder trazabilidad.
 *
 * Actores ........: Recepcionista, Médico, Paciente
 * Permiso ........: agenda.consultar
 * Tablas .........: paciente, cita, historial_estado_cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01).
 *
 * Flujo principal:
 *     1. El usuario busca al paciente por CI o nombre (el paciente ve directamente el suyo).
 *     2. El sistema muestra los datos básicos del paciente.
 *     3. El sistema lista su historial de citas ordenado de la más reciente a la más antigua.
 *     4. El usuario puede filtrar por estado o rango de fechas y ver los cambios de estado de una cita.
 *
 * Excepciones / reglas que debe controlar:
 *     - El paciente no tiene citas registradas.
 *     - Un paciente intenta consultar el historial de otro paciente: se deniega.
 *
 * Postcondición:
 *     Se muestra el historial completo del paciente.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu14ConsultarHistorialPaciente
{
    /** GET /api/v1/citas/pacientes/{id}/historial — Historial de citas de un paciente */
    public static function historial(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');
        throw new NoImplementado('CU14');
    }
    /** GET /api/v1/citas/{id}/cambios — Cambios de estado de una cita */
    public static function cambios(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');
        throw new NoImplementado('CU14');
    }
}
