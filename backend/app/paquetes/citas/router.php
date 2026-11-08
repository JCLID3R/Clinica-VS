<?php
/**
 * Endpoints del paquete citas: /api/v1/...
 * Cada endpoint llama a su caso de uso en casos_uso/.
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\Citas\CasosUso\Cu09ProgramarCita;
use App\Paquetes\Citas\CasosUso\Cu10ReprogramarCita;
use App\Paquetes\Citas\CasosUso\Cu11CancelarCita;
use App\Paquetes\Citas\CasosUso\Cu12RegistrarAsistencia;
use App\Paquetes\Citas\CasosUso\Cu13ConsultarAgendaMedica;
use App\Paquetes\Citas\CasosUso\Cu14ConsultarHistorialPaciente;

return static function (Enrutador $r): void {
    $r->post('/citas', [Cu09ProgramarCita::class, 'registrar'], 'CU09 - Programar cita');
    $r->get('/citas', [Cu10ReprogramarCita::class, 'buscarCitas'], 'CU10 - Buscar citas para reprogramar');
    $r->patch('/citas/{id}/reprogramar', [Cu10ReprogramarCita::class, 'reprogramar'], 'CU10 - Reprogramar cita');
    $r->patch('/citas/{id}/cancelar', [Cu11CancelarCita::class, 'cancelar'], 'CU11 - Cancelar cita con motivo');
    $r->get('/citas/asistencia', [Cu12RegistrarAsistencia::class, 'citasDelDia'], 'CU12 - Citas del día para registrar asistencia');
    $r->get('/citas/{id}/acciones', [Cu12RegistrarAsistencia::class, 'accionesValidas'], 'CU12 - Acciones válidas según el estado');
    $r->patch('/citas/{id}/estado', [Cu12RegistrarAsistencia::class, 'cambiarEstado'], 'CU12 - Confirmar, atender o registrar inasistencia');
    $r->get('/citas/inasistencias-pendientes', [Cu12RegistrarAsistencia::class, 'inasistenciasPendientes'], 'CU12 - Citas sin marcar de días pasados');
    $r->post('/citas/inasistencias-pendientes', [Cu12RegistrarAsistencia::class, 'marcarInasistencias'], 'CU12 - Marcar lote de inasistencias');
    $r->get('/citas/agenda', [Cu13ConsultarAgendaMedica::class, 'consultar'], 'CU13 - Agenda médica (?id_medico=&desde=&hasta=&estado=)');
    $r->get('/citas/pacientes', [Cu14ConsultarHistorialPaciente::class, 'buscarPacientes'], 'CU14 - Buscar pacientes para historial (?buscar=)');
    $r->get('/citas/pacientes/{id}/historial', [Cu14ConsultarHistorialPaciente::class, 'historial'], 'CU14 - Historial de citas de un paciente');
    $r->get('/citas/{id}/cambios', [Cu14ConsultarHistorialPaciente::class, 'cambios'], 'CU14 - Cambios de estado de una cita');
};
