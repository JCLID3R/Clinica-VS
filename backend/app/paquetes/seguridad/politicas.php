<?php
/**
 * Paquete seguridad — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - Solo el Administrador gestiona usuarios, roles y permisos (permisos usuario.gestionar y rol.gestionar).
 *   - Una cuenta 'inactiva' o 'bloqueada' no puede iniciar sesión; tras 5 intentos fallidos se bloquea.
 *   - El rol Administrador no puede quedarse sin el permiso rol.gestionar.
 *   - Las contraseñas se guardan con password_hash() y se verifican con password_verify().
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\Politicas;

use App\Nucleo\{ErrorApi, Sesion};

// ---- CU01: inicio ----
// Regla del docente: el bloqueo es al TERCER intento fallido (reemplaza los 5 del encabezado).

/** Intentos fallidos permitidos antes del primer bloqueo. */
const INTENTOS_PERMITIDOS = 3;

/** Duración máxima de un bloqueo progresivo (1, 2, 3, 3, 3... minutos). */
const MAX_MINUTOS_BLOQUEO = 3;

/** Caracteres especiales aceptados (también los usa la contraseña temporal). */
const ESPECIALES = '!@#$%&*?-_.+=';

/**
 * Contraseña segura: mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
 * Devuelve la lista de requisitos que faltan (vacía si cumple). La reutilizan CU02 y CU16.
 */
function validarContrasena(string $contrasena): array
{
    $faltan = [];
    if (mb_strlen($contrasena) < 8) {
        $faltan[] = 'al menos 8 caracteres';
    }
    if (!preg_match('/\p{Lu}/u', $contrasena)) {
        $faltan[] = 'una letra mayúscula';
    }
    if (!preg_match('/\p{Ll}/u', $contrasena)) {
        $faltan[] = 'una letra minúscula';
    }
    if (!preg_match('/\d/', $contrasena)) {
        $faltan[] = 'un número';
    }
    if (!preg_match('/[^\p{L}\d\s]/u', $contrasena)) {
        $faltan[] = 'un carácter especial';
    }
    return $faltan;
}

/** Mensaje con los requisitos que faltan, p. ej. "Debe tener: una letra mayúscula, un número." */
function mensajeContrasena(array $faltan): string
{
    return 'Debe tener: ' . implode(', ', $faltan) . '.';
}

/** Minutos del bloqueo según los intentos fallidos acumulados: numero_bloqueo = intentos - 2. */
function minutosBloqueo(int $intentosFallidos): int
{
    $numeroBloqueo = $intentosFallidos - (INTENTOS_PERMITIDOS - 1);
    return max(1, min($numeroBloqueo, MAX_MINUTOS_BLOQUEO));
}

/** Mensaje de cuenta bloqueada. */
function mensajeBloqueo(int $minutos): string
{
    return 'Cuenta bloqueada. Intente nuevamente en ' . $minutos . ($minutos === 1 ? ' minuto.' : ' minutos.');
}

/** Mensaje de credenciales incorrectas, sin indicar cuál dato falló. */
function mensajeCredenciales(int $intentosFallidos): string
{
    $quedan = INTENTOS_PERMITIDOS - $intentosFallidos;
    return 'Usuario o contraseña incorrectos. ' . ($quedan === 1 ? 'Le queda 1 intento.' : "Le quedan {$quedan} intentos.");
}

/** Contraseña temporal de 10 caracteres con random_int() que cumple validarContrasena(). */
function generarContrasenaTemporal(): string
{
    $grupos = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', ESPECIALES];
    $todos = implode('', $grupos);
    $caracteres = [];
    foreach ($grupos as $g) {
        $caracteres[] = $g[random_int(0, strlen($g) - 1)];
    }
    while (count($caracteres) < 10) {
        $caracteres[] = $todos[random_int(0, strlen($todos) - 1)];
    }
    // Mezcla de Fisher-Yates con random_int()
    for ($i = count($caracteres) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
    }
    return implode('', $caracteres);
}

/** IP del cliente para la bitácora (en Render llega en X-Forwarded-For). */
function ipCliente(): string
{
    $reenviada = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $ip = $reenviada !== '' ? trim(explode(',', $reenviada)[0]) : ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
}

/** Una cuenta se desbloquea sola solo si el bloqueo fue por intentos (no por el administrador). */
function esBloqueoPorIntentos(array $usuario, ?array $ultimoBloqueo): bool
{
    return $usuario['estado'] === 'bloqueado'
        && (int) $usuario['intentos_fallidos'] >= INTENTOS_PERMITIDOS
        && $ultimoBloqueo !== null;
}

/** Valida que la cuenta pueda iniciar sesión según su estado (la cuenta bloqueada se revisa aparte). */
function verificarCuentaHabilitada(array $usuario): void
{
    if ($usuario['estado'] === 'inactivo' || $usuario['rol_estado'] !== 'activo') {
        throw new ErrorApi(403, 'La cuenta está inactiva. El acceso no está permitido; comuníquese con el administrador.');
    }
}
// ---- CU01: fin ----

// ---- CU02: inicio ----
/** Genera una contraseña aleatoria que cumple validarContrasena(). */
function generarContrasena(): string
{
    return generarContrasenaTemporal();
}

/** Valida que el admin no intente auto-desactivarse o auto-bloquearse. */
function validarNoAutoDesactivacion(int $idUsuario, string $estadoNuevo): void
{
    $usuario = Sesion::usuario();
    if ($usuario['id_usuario'] === $idUsuario && $estadoNuevo !== 'activo') {
        throw new ErrorApi(403, 'No puede desactivar o bloquear su propia cuenta.');
    }
}
// ---- CU02: fin ----
