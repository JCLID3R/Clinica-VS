<?php
/**
 * Endpoints del paquete personas: /api/v1/...
 * Cada endpoint llama a su caso de uso en casos_uso/.
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\Personas\CasosUso\Cu04GestionarPacientes;
use App\Paquetes\Personas\CasosUso\Cu05GestionarMedicos;
use App\Paquetes\Personas\CasosUso\Cu06GestionarEspecialidades;

return static function (Enrutador $r): void {
    $r->get('/personas/pacientes', [Cu04GestionarPacientes::class, 'listar'], 'CU04 - Listar y buscar pacientes (?buscar=)');
    $r->get('/personas/pacientes/{id}', [Cu04GestionarPacientes::class, 'obtener'], 'CU04 - Datos de un paciente');
    $r->post('/personas/pacientes', [Cu04GestionarPacientes::class, 'registrar'], 'CU04 - Registrar paciente');
    $r->put('/personas/pacientes/{id}', [Cu04GestionarPacientes::class, 'modificar'], 'CU04 - Modificar paciente');
    $r->patch('/personas/pacientes/{id}/estado', [Cu04GestionarPacientes::class, 'cambiarEstado'], 'CU04 - Activar o desactivar paciente');
    $r->get('/personas/medicos', [Cu05GestionarMedicos::class, 'listar'], 'CU05 - Listar y buscar médicos');
    $r->post('/personas/medicos', [Cu05GestionarMedicos::class, 'registrar'], 'CU05 - Registrar médico');
    $r->put('/personas/medicos/{id}', [Cu05GestionarMedicos::class, 'modificar'], 'CU05 - Modificar médico y especialidades');
    $r->patch('/personas/medicos/{id}/estado', [Cu05GestionarMedicos::class, 'cambiarEstado'], 'CU05 - Activar o desactivar médico');
    $r->get('/personas/especialidades', [Cu06GestionarEspecialidades::class, 'listar'], 'CU06 - Listar especialidades');
    $r->post('/personas/especialidades', [Cu06GestionarEspecialidades::class, 'registrar'], 'CU06 - Registrar especialidad');
    $r->put('/personas/especialidades/{id}', [Cu06GestionarEspecialidades::class, 'modificar'], 'CU06 - Modificar especialidad');
    $r->patch('/personas/especialidades/{id}/estado', [Cu06GestionarEspecialidades::class, 'cambiarEstado'], 'CU06 - Activar o desactivar especialidad');
};
