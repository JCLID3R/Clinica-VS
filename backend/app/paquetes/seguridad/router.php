<?php
/**
 * Endpoints del paquete seguridad: /api/v1/...
 * Cada endpoint llama a su caso de uso en casos_uso/.
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\Seguridad\CasosUso\Cu01IniciarSesion;
use App\Paquetes\Seguridad\CasosUso\Cu02GestionarUsuarios;
use App\Paquetes\Seguridad\CasosUso\Cu03GestionarRolesPermisos;

return static function (Enrutador $r): void {
    $r->post('/seguridad/sesion', [Cu01IniciarSesion::class, 'iniciar'], 'CU01 - Iniciar sesión');
    $r->delete('/seguridad/sesion', [Cu01IniciarSesion::class, 'cerrar'], 'CU01 - Cerrar sesión');
    $r->get('/seguridad/sesion', [Cu01IniciarSesion::class, 'actual'], 'CU01 - Usuario de la sesión actual');
    $r->get('/seguridad/usuarios', [Cu02GestionarUsuarios::class, 'listar'], 'CU02 - Listar usuarios');
    $r->post('/seguridad/usuarios', [Cu02GestionarUsuarios::class, 'crear'], 'CU02 - Registrar usuario');
    $r->put('/seguridad/usuarios/{id}', [Cu02GestionarUsuarios::class, 'modificar'], 'CU02 - Modificar usuario');
    $r->patch('/seguridad/usuarios/{id}/estado', [Cu02GestionarUsuarios::class, 'cambiarEstado'], 'CU02 - Activar o desactivar usuario');
    $r->get('/seguridad/roles', [Cu03GestionarRolesPermisos::class, 'listarRoles'], 'CU03 - Listar roles');
    $r->get('/seguridad/permisos', [Cu03GestionarRolesPermisos::class, 'listarPermisos'], 'CU03 - Listar permisos');
    $r->get('/seguridad/roles/{id}/permisos', [Cu03GestionarRolesPermisos::class, 'permisosDelRol'], 'CU03 - Permisos de un rol');
    $r->put('/seguridad/roles/{id}/permisos', [Cu03GestionarRolesPermisos::class, 'asignarPermisos'], 'CU03 - Asignar permisos a un rol');

    // ---- CU01: inicio ----
    $r->post('/seguridad/recuperar', [Cu01IniciarSesion::class, 'recuperar'], 'CU01 - Recuperar contraseña (público)');
    $r->put('/seguridad/contrasena', [Cu01IniciarSesion::class, 'cambiarContrasena'], 'CU01 - Cambiar contraseña');
    // ---- CU01: fin ----
};
