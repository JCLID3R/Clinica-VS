<?php
/**
 * Endpoints del paquete agenda: /api/v1/...
 * Cada endpoint llama a su caso de uso en casos_uso/.
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\Agenda\CasosUso\Cu07GestionarHorarios;
use App\Paquetes\Agenda\CasosUso\Cu08ConsultarDisponibilidad;

return static function (Enrutador $r): void {
    $r->get('/agenda/medicos/{id}/horarios', [Cu07GestionarHorarios::class, 'listar'], 'CU07 - Horario semanal de un médico');
    $r->post('/agenda/medicos/{id}/horarios', [Cu07GestionarHorarios::class, 'agregar'], 'CU07 - Agregar bloque de horario');
    $r->put('/agenda/horarios/{id}', [Cu07GestionarHorarios::class, 'modificar'], 'CU07 - Modificar bloque');
    $r->patch('/agenda/horarios/{id}/estado', [Cu07GestionarHorarios::class, 'cambiarEstado'], 'CU07 - Activar o desactivar bloque');
    $r->get('/agenda/bloqueos', [Cu07GestionarHorarios::class, 'listarBloqueos'], 'CU07 - Fechas bloqueadas');
    $r->post('/agenda/bloqueos', [Cu07GestionarHorarios::class, 'registrarBloqueo'], 'CU07 - Registrar fecha bloqueada');
    $r->get('/agenda/especialidades/{id}/medicos', [Cu08ConsultarDisponibilidad::class, 'medicosPorEspecialidad'], 'CU08 - Médicos activos de una especialidad');
    $r->get('/agenda/disponibilidad', [Cu08ConsultarDisponibilidad::class, 'horariosDisponibles'], 'CU08 - Horarios libres (?id_medico=&fecha=)');
};
