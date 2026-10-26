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
