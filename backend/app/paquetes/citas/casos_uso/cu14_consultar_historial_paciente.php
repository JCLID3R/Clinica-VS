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

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Citas\Repository\{citasPorPaciente, historialPorCita, citaObtener, pacientesBuscarParaHistorial, medicoPorUsuario};
use function App\Paquetes\Personas\Repository\{pacientePorUsuario};

final class Cu14ConsultarHistorialPaciente
{
    /** GET /api/v1/citas/pacientes — Buscar pacientes para historial (?buscar=) */
    public static function buscarPacientes(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');

        $buscar = $p->consulta('buscar') ? trim($p->consulta('buscar')) : null;

        // Si el usuario es Médico, obtener su ID y filtrar por sus citas
        $s = Sesion::usuario();
        $idMedico = null;

        if ($s['rol'] === 'Medico') {
            $medico = medicoPorUsuario($s['id_usuario']);
            if ($medico) {
                $idMedico = $medico['id_medico'];
            }
        }

        $pacientes = pacientesBuscarParaHistorial($idMedico, $buscar);

        return [
            'pacientes' => $pacientes,
        ];
    }

    /** GET /api/v1/citas/pacientes/{id}/historial — Historial de citas de un paciente */
    public static function historial(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');

        $idPaciente = (int) $p->param('id');
        $estado = $p->consulta('estado') ? trim($p->consulta('estado')) : null;
        $desde = $p->consulta('desde') ?? null;
        $hasta = $p->consulta('hasta') ?? null;

        if (!$idPaciente) throw new ErrorApi(400, 'ID de paciente requerido.');

        $s = Sesion::usuario();
        $idMedico = null;

        if ($s['rol'] === 'Paciente') {
            $paciente = pacientePorUsuario($s['id_usuario']);
            if (!$paciente || $paciente['id_paciente'] !== $idPaciente) {
                throw new ErrorApi(403, 'No puedes ver el historial de otro paciente.');
            }
        } elseif ($s['rol'] === 'Medico') {
            // Si es Médico, obtener su ID y filtrar por sus citas
            $medico = medicoPorUsuario($s['id_usuario']);
            if ($medico) {
                $idMedico = $medico['id_medico'];
            }
        }

        $citas = citasPorPaciente($idPaciente, $estado, $desde, $hasta, $idMedico);

        $resumen = [
            'total' => 0,
            'programada' => 0,
            'confirmada' => 0,
            'atendida' => 0,
            'cancelada' => 0,
            'inasistencia' => 0,
        ];

        foreach ($citas as $cita) {
            $resumen['total']++;
            $estado_lower = strtolower($cita['estado']);
            if (isset($resumen[$estado_lower])) {
                $resumen[$estado_lower]++;
            }
        }

        return [
            'citas' => $citas,
            'resumen' => $resumen,
        ];
    }

    /** GET /api/v1/citas/{id}/cambios — Cambios de estado de una cita */
    public static function cambios(Peticion $p): mixed
    {
        Sesion::requerir('agenda.consultar');

        $idCita = (int) $p->param('id');
        if (!$idCita) throw new ErrorApi(400, 'ID de cita requerido.');

        $cita = citaObtener($idCita);
        if (!$cita) throw new ErrorApi(404, 'Cita no encontrada.');

        // Si es Médico, validar que sea su cita
        $s = Sesion::usuario();
        if ($s['rol'] === 'Medico') {
            $medico = medicoPorUsuario($s['id_usuario']);
            if (!$medico || $medico['id_medico'] !== $cita['id_medico']) {
                throw new ErrorApi(403, 'No puedes ver los cambios de una cita de otro médico.');
            }
        }

        $cambios = historialPorCita($idCita);

        return [
            'cita' => [
                'id_cita' => $cita['id_cita'],
                'codigo' => $cita['codigo'],
                'paciente' => $cita['paciente_nombres'] . ' ' . $cita['paciente_apellidos'],
                'medico' => $cita['medico_nombres'] . ' ' . $cita['medico_apellidos'],
                'especialidad' => $cita['especialidad_nombre'],
                'estado_actual' => $cita['estado'],
            ],
            'cambios' => $cambios,
        ];
    }
}
