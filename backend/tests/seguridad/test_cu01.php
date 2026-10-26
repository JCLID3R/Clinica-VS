<?php
/**
 * Pruebas de CU01 - Iniciar y cerrar sesión (las carga tests/ejecutar.php).
 * Usa admin y jperez (contraseña Clinica2026*). Al terminar deja a jperez como estaba
 * (estado, intentos y contraseña). No ejecuta los flujos que envían correo real
 * (recuperación con correo registrado y cambio exitoso de contraseña).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../app/nucleo/cargador.php';

use App\Nucleo\BaseDatos;

/** Petición con cookie de sesión propia ($jar guarda la cookie entre llamadas). */
function cu01_peticion(string $metodo, string $ruta, ?array $cuerpo, ?string &$jar): array
{
    $cabeceras = "Content-Type: application/json\r\n" . ($jar ? "Cookie: {$jar}\r\n" : '');
    $ctx = stream_context_create(['http' => [
        'method' => $metodo, 'ignore_errors' => true, 'header' => $cabeceras,
        'content' => $cuerpo !== null ? json_encode($cuerpo) : '',
    ]]);
    $texto = file_get_contents(API . $ruta, false, $ctx);
    preg_match('#HTTP/\S+ (\d{3})#', $http_response_header[0] ?? '', $m);
    foreach ($http_response_header as $h) {
        if (preg_match('/^Set-Cookie:\s*(clinica_sesion=[^;]*)/i', $h, $c)) {
            $jar = str_ends_with($c[1], '=deleted') || str_contains($h, 'Max-Age=0') ? null : $c[1];
        }
    }
    return [(int) ($m[1] ?? 0), json_decode($texto ?: 'null', true)];
}

echo "\nCU01 - Iniciar y cerrar sesión\n";
$original = BaseDatos::fila("SELECT password_hash FROM usuario WHERE username = 'jperez'");
BaseDatos::ejecutar("UPDATE usuario SET estado = 'activo', intentos_fallidos = 0 WHERE username = 'jperez'");

try {
    // ---- Inicio de sesión correcto, sesión actual y cierre ----
    $jar = null;
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'admin', 'contrasena' => 'Clinica2026*'], $jar);
    prueba('admin / Clinica2026* inicia sesión', $e === 201 && ($r['rol'] ?? '') === 'Administrador' && in_array('usuario.gestionar', $r['permisos'] ?? [], true));
    prueba('Login correcto no exige cambio de contraseña', ($r['debe_cambiar_contrasena'] ?? null) === false);
    [$e, $r] = cu01_peticion('GET', '/seguridad/sesion', null, $jar);
    prueba('GET /seguridad/sesion devuelve el usuario de la sesión', $e === 200 && ($r['username'] ?? '') === 'admin');

    // ---- Cambio de contraseña: validaciones (no se guarda nada) ----
    [$e, $r] = cu01_peticion('PUT', '/seguridad/contrasena', ['actual' => 'Clinica2026*', 'nueva' => 'Clinica2026', 'confirmacion' => 'Clinica2026'], $jar);
    prueba('Contraseña nueva sin carácter especial se rechaza', $e === 400 && str_contains($r['error']['detalle']['nueva'] ?? '', 'carácter especial'));
    [$e, $r] = cu01_peticion('PUT', '/seguridad/contrasena', ['actual' => 'Clinica2026*', 'nueva' => 'corta1*', 'confirmacion' => 'corta1*'], $jar);
    prueba('Contraseña corta y sin mayúscula indica lo que falta', $e === 400
        && str_contains($r['error']['detalle']['nueva'] ?? '', '8 caracteres') && str_contains($r['error']['detalle']['nueva'] ?? '', 'mayúscula'));
    [$e, $r] = cu01_peticion('PUT', '/seguridad/contrasena', ['actual' => 'Clinica2026*', 'nueva' => 'Nueva#2026a', 'confirmacion' => 'Nueva#2026b'], $jar);
    prueba('La confirmación debe coincidir', $e === 400 && isset($r['error']['detalle']['confirmacion']));
    [$e, $r] = cu01_peticion('PUT', '/seguridad/contrasena', ['actual' => 'Incorrecta#1', 'nueva' => 'Nueva#2026a', 'confirmacion' => 'Nueva#2026a'], $jar);
    prueba('La contraseña actual se verifica', $e === 400 && isset($r['error']['detalle']['actual']));
    [$e, $r] = cu01_peticion('PUT', '/seguridad/contrasena', ['actual' => 'Clinica2026*', 'nueva' => 'Clinica2026*', 'confirmacion' => 'Clinica2026*'], $jar);
    prueba('La nueva contraseña debe ser distinta de la actual', $e === 400 && str_contains($r['error']['detalle']['nueva'] ?? '', 'distinta'));

    [$e] = cu01_peticion('DELETE', '/seguridad/sesion', null, $jar);
    prueba('Cerrar sesión responde 204', $e === 204);
    [$e] = cu01_peticion('GET', '/seguridad/sesion', null, $jar);
    prueba('Después de cerrar sesión ya no hay sesión (401)', $e === 401);
    [$e] = cu01_peticion('PUT', '/seguridad/contrasena', ['actual' => 'a', 'nueva' => 'b', 'confirmacion' => 'b'], $jar);
    prueba('Cambiar contraseña exige sesión (401)', $e === 401);

    // ---- Validación de datos ----
    $jar = null;
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', ['login' => '', 'contrasena' => ''], $jar);
    prueba('Campos vacíos responden 400 con el detalle por campo', $e === 400 && isset($r['error']['detalle']['login'], $r['error']['detalle']['contrasena']));
    [$e] = cu01_peticion('POST', '/seguridad/sesion', ['login' => ['x'], 'contrasena' => 1], $jar);
    prueba('Tipos de dato incorrectos responden 400', $e === 400);

    // ---- Intentos fallidos y bloqueo progresivo ----
    $jar = null;
    $mala = ['login' => 'jperez', 'contrasena' => 'Incorrecta#1'];
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', $mala, $jar);
    prueba('1.er fallo: "Le quedan 2 intentos" sin decir qué dato falló', $e === 401 && ($r['error']['mensaje'] ?? '') === 'Usuario o contraseña incorrectos. Le quedan 2 intentos.');
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', $mala, $jar);
    prueba('2.º fallo: "Le queda 1 intento"', $e === 401 && str_contains($r['error']['mensaje'] ?? '', 'Le queda 1 intento.'));
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', $mala, $jar);
    prueba('3.er fallo: bloqueo de 1 minuto', $e === 423 && ($r['error']['mensaje'] ?? '') === 'Cuenta bloqueada. Intente nuevamente en 1 minuto.');
    $fila = BaseDatos::fila("SELECT estado, intentos_fallidos FROM usuario WHERE username = 'jperez'");
    prueba('La cuenta queda en estado bloqueado con 3 intentos', $fila['estado'] === 'bloqueado' && (int) $fila['intentos_fallidos'] === 3);
    $bloqueo = BaseDatos::fila("SELECT 1 FROM bitacora WHERE id_usuario = 3 AND accion = 'BLOQUEO_INTENTOS' AND fecha > now() - interval '1 minute'");
    prueba('Se registra BLOQUEO_INTENTOS en la bitácora', $bloqueo !== null);
    [$e] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'jperez', 'contrasena' => 'Clinica2026*'], $jar);
    prueba('Durante el bloqueo no entra aunque la contraseña sea correcta', $e === 423);

    // Bloqueo vencido: se simula moviendo el inicio del bloqueo 2 minutos atrás
    BaseDatos::ejecutar("UPDATE bitacora SET fecha = fecha - interval '2 minutes'
                          WHERE id_bitacora = (SELECT max(id_bitacora) FROM bitacora WHERE id_usuario = 3 AND accion = 'BLOQUEO_INTENTOS')");
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', $mala, $jar);
    prueba('Vencido el bloqueo, un nuevo fallo bloquea 2 minutos', $e === 423 && str_contains($r['error']['mensaje'] ?? '', '2 minutos'));
    BaseDatos::ejecutar("UPDATE bitacora SET fecha = fecha - interval '3 minutes'
                          WHERE id_bitacora = (SELECT max(id_bitacora) FROM bitacora WHERE id_usuario = 3 AND accion = 'BLOQUEO_INTENTOS')");
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'jperez', 'contrasena' => 'Clinica2026*'], $jar);
    prueba('Vencido el bloqueo se desbloquea solo y entra', $e === 201 && ($r['username'] ?? '') === 'jperez');
    $fila = BaseDatos::fila("SELECT estado, intentos_fallidos FROM usuario WHERE username = 'jperez'");
    prueba('El login correcto reinicia intentos_fallidos a 0', $fila['estado'] === 'activo' && (int) $fila['intentos_fallidos'] === 0);

    // Bloqueo hecho por el administrador (intentos en 0): no se desbloquea solo
    BaseDatos::ejecutar("UPDATE usuario SET estado = 'bloqueado' WHERE username = 'jperez'");
    $jar = null;
    [$e] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'jperez', 'contrasena' => 'Clinica2026*'], $jar);
    prueba('Cuenta bloqueada por el administrador no entra', $e === 403);
    BaseDatos::ejecutar("UPDATE usuario SET estado = 'inactivo' WHERE username = 'jperez'");
    [$e] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'jperez', 'contrasena' => 'Clinica2026*'], $jar);
    prueba('Cuenta inactiva no entra', $e === 403);
    BaseDatos::ejecutar("UPDATE usuario SET estado = 'activo' WHERE username = 'jperez'");

    // ---- Usuario inexistente: mismos mensajes que una cuenta real ----
    $jar = null;
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'no_existe_cu01', 'contrasena' => 'Incorrecta#1'], $jar);
    prueba('Usuario inexistente recibe el mismo mensaje genérico', $e === 401 && str_contains($r['error']['mensaje'] ?? '', 'Le quedan 2 intentos.'));

    // ---- Recuperación: la respuesta no revela si el correo existe ----
    [$e, $r] = cu01_peticion('POST', '/seguridad/recuperar', ['correo' => 'no.registrado@ejemplo.com'], $jar);
    prueba('Recuperar con correo no registrado responde el mensaje genérico', $e === 201 && str_starts_with($r['mensaje'] ?? '', 'Si el correo está registrado'));
    [$e] = cu01_peticion('POST', '/seguridad/recuperar', ['correo' => 'no-es-correo'], $jar);
    prueba('Recuperar con correo inválido responde 400', $e === 400);

    // ---- Cambio obligatorio: la última acción registrada es la recuperación ----
    BaseDatos::ejecutar("INSERT INTO bitacora (id_usuario, accion, tabla, id_registro, detalle, ip)
                         VALUES (3, 'RECUPERACION_CONTRASENA', 'usuario', 3, 'Prueba automática CU01', '127.0.0.1')");
    $jar = null;
    [$e, $r] = cu01_peticion('POST', '/seguridad/sesion', ['login' => 'jperez', 'contrasena' => 'Clinica2026*'], $jar);
    prueba('Tras la recuperación el login exige cambiar la contraseña', $e === 201 && ($r['debe_cambiar_contrasena'] ?? false) === true && ($r['permisos'] ?? null) === []);
    [$e] = cu01_peticion('GET', '/agenda/disponibilidad', null, $jar);
    prueba('Sin cambiarla, el resto del sistema responde 403', $e === 403);
    cu01_peticion('DELETE', '/seguridad/sesion', null, $jar);
} finally {
    BaseDatos::ejecutar("INSERT INTO bitacora (id_usuario, accion, tabla, id_registro, detalle, ip)
                         SELECT 3, 'CAMBIO_CONTRASENA', 'usuario', 3, 'Prueba automática CU01 (restaura el estado)', '127.0.0.1'
                          WHERE EXISTS (SELECT 1 FROM bitacora WHERE id_usuario = 3 AND detalle = 'Prueba automática CU01')");
    BaseDatos::ejecutar("UPDATE usuario SET estado = 'activo', intentos_fallidos = 0, password_hash = :h WHERE username = 'jperez'",
        [':h' => $original['password_hash']]);
}
