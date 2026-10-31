<?php
/**
 * Paquete seguridad — validación de los datos que entran y salen de la API.
 * Cada función recibe el cuerpo JSON de la petición y devuelve los datos limpios,
 * o lanza ErrorApi(400) con los errores por campo (usar App\Nucleo\Validador).
 *
 * Estado: PENDIENTE. Agregar aquí una función por formulario, p. ej.:
 *     function validar_<entidad>(array $datos): array { ... }
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\Schemas;

// ---- CU01: inicio ----
use App\Nucleo\Validador;
use function App\Paquetes\Seguridad\Politicas\{validarContrasena, mensajeContrasena};

/** Los campos de texto deben llegar como texto (un arreglo o un número se rechaza con 400). */
function exigir_texto(array $datos, string ...$campos): void
{
    $errores = [];
    foreach ($campos as $c) {
        if (isset($datos[$c]) && !is_string($datos[$c])) {
            $errores[$c] = 'Debe ser texto.';
        }
    }
    if ($errores) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', $errores);
    }
}

/** POST /seguridad/sesion: {login, contrasena}. El formato de la contraseña no se revela aquí. */
function validar_login(array $datos): array
{
    exigir_texto($datos, 'login', 'contrasena');
    $v = new Validador($datos);
    $v->requerido('login', 'contrasena')->texto('login', 100)->texto('contrasena', 72);
    $v->verificar();
    return ['login' => trim((string) $datos['login']), 'contrasena' => (string) $datos['contrasena']];
}

/** POST /seguridad/recuperar: {correo} */
function validar_recuperar(array $datos): array
{
    exigir_texto($datos, 'correo');
    $v = new Validador($datos);
    $v->requerido('correo')->texto('correo', 100)->correo('correo');
    $v->verificar();
    return ['correo' => mb_strtolower(trim((string) $datos['correo']))];
}

/** PUT /seguridad/contrasena: {actual, nueva, confirmacion} */
function validar_cambio_contrasena(array $datos): array
{
    exigir_texto($datos, 'actual', 'nueva', 'confirmacion');
    $v = new Validador($datos);
    $v->requerido('actual', 'nueva', 'confirmacion')->texto('actual', 72)->texto('nueva', 72)->texto('confirmacion', 72);
    $v->verificar();

    $errores = [];
    $nueva = (string) $datos['nueva'];
    $faltan = validarContrasena($nueva);
    if ($faltan) {
        $errores['nueva'] = mensajeContrasena($faltan);
    }
    if ($nueva !== (string) $datos['confirmacion']) {
        $errores['confirmacion'] = 'La confirmación no coincide con la nueva contraseña.';
    }
    if ($errores) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', $errores);
    }
    return ['actual' => (string) $datos['actual'], 'nueva' => $nueva];
}
// ---- CU01: fin ----

// ---- CU02: inicio ----
/** GET /seguridad/usuarios: {filtro?, rol?, estado?} */
function validar_listar_usuarios(array $datos): array
{
    exigir_texto($datos, 'filtro', 'estado');
    $v = new Validador($datos);
    $v->texto('filtro', 100)->entero('rol')->texto('estado', 10);
    $v->verificar();
    return [
        'filtro' => isset($datos['filtro']) && $datos['filtro'] ? trim((string) $datos['filtro']) : null,
        'rol' => isset($datos['rol']) && $datos['rol'] ? (int) $datos['rol'] : null,
        'estado' => isset($datos['estado']) && $datos['estado'] ? (string) $datos['estado'] : null,
    ];
}

/** POST /seguridad/usuarios: {username, correo, contrasena?, rol, id_paciente?, id_medico?} */
function validar_crear_usuario(array $datos): array
{
    exigir_texto($datos, 'username', 'correo', 'contrasena', 'rol');
    $v = new Validador($datos);
    $v->requerido('username', 'correo', 'rol')->texto('username', 40)->texto('correo', 100)->texto('rol', 30);
    $v->correo('correo')->texto('contrasena', 72)->entero('id_paciente')->entero('id_medico');
    $v->verificar();

    $errores = [];
    $contrasena = (string) ($datos['contrasena'] ?? '');
    if ($contrasena) {
        $faltan = validarContrasena($contrasena);
        if ($faltan) {
            $errores['contrasena'] = mensajeContrasena($faltan);
        }
    }
    if ($errores) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', $errores);
    }

    return [
        'username' => trim((string) $datos['username']),
        'correo' => mb_strtolower(trim((string) $datos['correo'])),
        'contrasena' => $contrasena,
        'rol' => trim((string) $datos['rol']),
        'id_paciente' => isset($datos['id_paciente']) ? (int) $datos['id_paciente'] : null,
        'id_medico' => isset($datos['id_medico']) ? (int) $datos['id_medico'] : null,
    ];
}

/** PUT /seguridad/usuarios/{id}: {username, correo, rol} */
function validar_modificar_usuario(array $datos): array
{
    exigir_texto($datos, 'username', 'correo', 'rol');
    $v = new Validador($datos);
    $v->requerido('username', 'correo', 'rol')->texto('username', 40)->texto('correo', 100)->texto('rol', 30);
    $v->correo('correo');
    $v->verificar();

    return [
        'username' => trim((string) $datos['username']),
        'correo' => mb_strtolower(trim((string) $datos['correo'])),
        'rol' => trim((string) $datos['rol']),
    ];
}

/** PATCH /seguridad/usuarios/{id}/estado: {estado} */
function validar_cambiar_estado_usuario(array $datos): array
{
    exigir_texto($datos, 'estado');
    $v = new Validador($datos);
    $v->requerido('estado')->texto('estado', 10);
    $v->verificar();

    $estado = (string) $datos['estado'];
    if (!in_array($estado, ['activo', 'inactivo', 'bloqueado'])) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', ['estado' => 'Estado inválido.']);
    }

    return ['estado' => $estado];
}
// ---- CU02: fin ----

// ---- CU03: inicio ----
/** PUT /seguridad/roles/{id}/permisos: {permisos: [id_permiso, ...]} */
function validar_asignar_permisos(array $datos): array
{
    exigir_texto($datos);
    $v = new Validador($datos);
    if (!isset($datos['permisos']) || !is_array($datos['permisos'])) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', ['permisos' => 'Debe ser una lista de permisos.']);
    }
    $permisos = [];
    foreach ($datos['permisos'] as $p) {
        if (!is_int($p) && !is_string($p)) {
            throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', ['permisos' => 'Cada permiso debe ser un número.']);
        }
        $permisos[] = (int) $p;
    }
    return ['permisos' => $permisos];
}
// ---- CU03: fin ----

// ---- BITACORA: inicio ----
/** GET /seguridad/bitacora: filtros y paginación. */
function validar_listar_bitacora(array $params): array
{
    $resultado = [
        'usuario' => trim((string) ($params['usuario'] ?? '')),
        'tabla' => trim((string) ($params['tabla'] ?? '')),
        'accion' => trim((string) ($params['accion'] ?? '')),
        'desde' => trim((string) ($params['desde'] ?? '')),
        'hasta' => trim((string) ($params['hasta'] ?? '')),
        'pagina' => max(1, (int) ($params['pagina'] ?? 1)),
        'por_pagina' => min(100, max(1, (int) ($params['por_pagina'] ?? 25))),
    ];

    // Validar fechas si están presentes
    if (!empty($resultado['desde']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $resultado['desde'])) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', ['desde' => 'Formato: YYYY-MM-DD']);
    }
    if (!empty($resultado['hasta']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $resultado['hasta'])) {
        throw new \App\Nucleo\ErrorApi(400, 'Datos inválidos.', ['hasta' => 'Formato: YYYY-MM-DD']);
    }

    return $resultado;
}
// ---- BITACORA: fin ----
