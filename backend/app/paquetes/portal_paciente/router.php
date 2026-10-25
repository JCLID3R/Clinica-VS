<?php
/**
 * Endpoints del paquete portal_paciente: /api/v1/...
 * Cada endpoint llama a su caso de uso en casos_uso/.
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\PortalPaciente\CasosUso\Cu16GestionarPerfilPaciente;
use App\Paquetes\PortalPaciente\CasosUso\Cu17ReservarMisCitas;

return static function (Enrutador $r): void {
    $r->post('/portal/registro', [Cu16GestionarPerfilPaciente::class, 'registrarCuenta'], 'CU16 - Crear cuenta del portal');
    $r->get('/portal/perfil', [Cu16GestionarPerfilPaciente::class, 'obtenerPerfil'], 'CU16 - Mi perfil');
    $r->put('/portal/perfil', [Cu16GestionarPerfilPaciente::class, 'actualizarPerfil'], 'CU16 - Actualizar mi perfil');
    $r->get('/portal/citas', [Cu17ReservarMisCitas::class, 'misCitas'], 'CU17 - Mis citas por estado');
    $r->post('/portal/citas', [Cu17ReservarMisCitas::class, 'reservar'], 'CU17 - Reservar cita en línea');
};
