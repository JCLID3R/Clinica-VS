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
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu17ReservarMisCitas
{
    /** GET /api/v1/portal/citas — Mis citas por estado */
    public static function misCitas(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        throw new NoImplementado('CU17');
    }
    /** POST /api/v1/portal/citas — Reservar cita en línea */
    public static function reservar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        throw new NoImplementado('CU17');
    }
}
