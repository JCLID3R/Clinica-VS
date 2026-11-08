<?php
/**
 * CU13 - Consultar agenda médica
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Alta  ·  Requisito: RF11
 *
 * Consultar la agenda de un médico para una fecha, con hora, código, paciente, especialidad, estado
 * y motivo (si el usuario tiene permiso), filtrando por médico, fecha y estado.
 *
 * Actores ........: Médico, Recepcionista, Administrador
 * Permiso ........: agenda.consultar
 * Tablas .........: cita, medico, paciente
 *
 * Precondición:
 *     El usuario inició sesión (CU01) y tiene el permiso agenda.consultar.
 *
 * Flujo principal:
 *     1. El usuario ingresa a «Agenda médica».
 *     2. Si el usuario es Médico, el sistema fija su propio médico; la recepcionista o el administrador seleccionan el médico.
 *     3. El usuario selecciona la fecha y, opcionalmente, un estado.
 *     4. El sistema consulta las citas y las muestra ordenadas por hora, con colores por estado (pendiente, atendida, cancelada).
 *     5. El usuario puede seleccionar una cita para ver los datos básicos del paciente o registrar su asistencia (CU12).
 *
 * Excepciones / reglas que debe controlar:
 *     - No hay citas para los filtros seleccionados.
 *     - Un médico intenta ver la agenda de otro médico: se deniega.
 *
 * Postcondición:
 *     Se muestra la agenda filtrada; no se modifica información.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Citas\Repository\{agendaPorMedico, bloqueosEnRango, medicoPorUsuario, citaObtener, historialPorCita, citasPorPaciente};
use function App\Paquetes\Personas\Repository\{pacientePorUsuario};

final class Cu13ConsultarAgendaMedica
{
    /** GET /api/v1/citas/agenda — Agenda médica (?id_medico=&desde=&hasta=&estado=) */
    public static function consultar(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');

        $s = Sesion::usuario();
        $idMedico = $p->consulta('id_medico') ? (int) $p->consulta('id_medico') : null;
        $desde = $p->consulta('desde') ?? date('Y-m-d');
        $hasta = $p->consulta('hasta') ?? date('Y-m-d', strtotime('+7 days'));

        if (strtolower($s['rol']) === 'medico') {
            $medico = medicoPorUsuario($s['id_usuario']);
            if (!$medico) throw new ErrorApi(403, 'Usuario no asociado a un médico.');
            $idMedico = $medico['id_medico'];
        }
        if (!$idMedico) throw new ErrorApi(400, 'Médico requerido.');

        $citas = agendaPorMedico($idMedico, $desde, $hasta);
        $bloqueos = bloqueosEnRango($desde, $hasta, $idMedico);

        return [
            'citas' => $citas,
            'bloqueos' => $bloqueos,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }
}
