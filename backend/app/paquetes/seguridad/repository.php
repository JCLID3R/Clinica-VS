<?php
/**
 * Paquete seguridad — acceso a datos (consultas SQL parametrizadas con PDO).
 * Solo este archivo escribe SQL del paquete. Usa App\Nucleo\BaseDatos::consultar(), fila(), ejecutar() y transaccion().
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\Repository;

use App\Nucleo\BaseDatos;

// ---- CU01: inicio ----
/** Columnas del usuario que usa CU01 (con el nombre y estado de su rol). */
const SQL_USUARIO_CU01 = "SELECT u.id_usuario, u.id_rol, u.username, u.correo, u.password_hash, u.estado,
                                 u.intentos_fallidos, u.ultimo_acceso, r.nombre AS rol, r.estado AS rol_estado
                            FROM usuario u
                            JOIN rol r ON r.id_rol = u.id_rol";

/** Busca la cuenta por nombre de usuario o correo (sin distinguir mayúsculas). $bloquear = FOR UPDATE. */
function buscarUsuarioPorLogin(string $login, bool $bloquear = false): ?array
{
    return BaseDatos::fila(
        SQL_USUARIO_CU01 . ' WHERE lower(u.username) = lower(:l1) OR lower(u.correo) = lower(:l2)
                            ORDER BY (lower(u.username) = lower(:l3)) DESC LIMIT 1' . ($bloquear ? ' FOR UPDATE OF u' : ''),
        [':l1' => $login, ':l2' => $login, ':l3' => $login]
    );
}

function buscarUsuarioPorCorreo(string $correo, bool $bloquear = false): ?array
{
    return BaseDatos::fila(
        SQL_USUARIO_CU01 . ' WHERE lower(u.correo) = lower(:c)' . ($bloquear ? ' FOR UPDATE OF u' : ''),
        [':c' => $correo]
    );
}

function buscarUsuarioPorId(int $idUsuario, bool $bloquear = false): ?array
{
    return BaseDatos::fila(
        SQL_USUARIO_CU01 . ' WHERE u.id_usuario = :id' . ($bloquear ? ' FOR UPDATE OF u' : ''),
        [':id' => $idUsuario]
    );
}

/** Códigos de permiso del rol. */
function permisosDelRol(int $idRol): array
{
    $filas = BaseDatos::consultar(
        'SELECT p.codigo FROM rol_permiso rp JOIN permiso p ON p.id_permiso = rp.id_permiso
          WHERE rp.id_rol = :r ORDER BY p.codigo',
        [':r' => $idRol]
    );
    return array_column($filas, 'codigo');
}

/** Registra una acción en la bitácora. */
function registrarBitacora(?int $idUsuario, string $accion, ?int $idRegistro, string $detalle, string $ip): void
{
    BaseDatos::ejecutar(
        "INSERT INTO bitacora (id_usuario, accion, tabla, id_registro, detalle, ip)
         VALUES (:u, :a, 'usuario', :r, :d, :ip)",
        [':u' => $idUsuario, ':a' => $accion, ':r' => $idRegistro, ':d' => $detalle, ':ip' => mb_substr($ip, 0, 45)]
    );
}

/** Suma un intento fallido y devuelve el total acumulado. */
function sumarIntentoFallido(int $idUsuario): int
{
    $fila = BaseDatos::fila(
        'UPDATE usuario SET intentos_fallidos = intentos_fallidos + 1 WHERE id_usuario = :id RETURNING intentos_fallidos',
        [':id' => $idUsuario]
    );
    return (int) $fila['intentos_fallidos'];
}

function cambiarEstadoUsuario(int $idUsuario, string $estado): void
{
    BaseDatos::ejecutar('UPDATE usuario SET estado = :e WHERE id_usuario = :id', [':e' => $estado, ':id' => $idUsuario]);
}

/** Login correcto: reinicia el contador y guarda el último acceso. */
function registrarAccesoCorrecto(int $idUsuario): void
{
    BaseDatos::ejecutar(
        'UPDATE usuario SET intentos_fallidos = 0, ultimo_acceso = now() WHERE id_usuario = :id',
        [':id' => $idUsuario]
    );
}

/**
 * Último registro BLOQUEO_INTENTOS del usuario y cuánto falta para que venza un bloqueo de $minutos
 * (calculado con la hora de PostgreSQL). Devuelve null si no existe.
 */
function ultimoBloqueoIntentos(int $idUsuario, int $minutos): ?array
{
    return BaseDatos::fila(
        "SELECT fecha,
                GREATEST(0, CEIL(EXTRACT(EPOCH FROM (fecha + make_interval(mins => CAST(:m AS int)) - now()))))::int AS segundos_restantes
           FROM bitacora
          WHERE id_usuario = :id AND accion = 'BLOQUEO_INTENTOS'
          ORDER BY fecha DESC, id_bitacora DESC LIMIT 1",
        [':id' => $idUsuario, ':m' => $minutos]
    );
}

/** true si la última acción entre RECUPERACION_CONTRASENA y CAMBIO_CONTRASENA es la recuperación. */
function debeCambiarContrasena(int $idUsuario): bool
{
    $fila = BaseDatos::fila(
        "SELECT accion FROM bitacora
          WHERE id_usuario = :id AND accion IN ('RECUPERACION_CONTRASENA', 'CAMBIO_CONTRASENA')
          ORDER BY fecha DESC, id_bitacora DESC LIMIT 1",
        [':id' => $idUsuario]
    );
    return ($fila['accion'] ?? '') === 'RECUPERACION_CONTRASENA';
}

/** Recuperación: nueva contraseña, contador en 0 y cuenta activa. */
function restablecerContrasena(int $idUsuario, string $hash): void
{
    BaseDatos::ejecutar(
        "UPDATE usuario SET password_hash = :h, intentos_fallidos = 0, estado = 'activo' WHERE id_usuario = :id",
        [':h' => $hash, ':id' => $idUsuario]
    );
}

function actualizarContrasena(int $idUsuario, string $hash): void
{
    BaseDatos::ejecutar('UPDATE usuario SET password_hash = :h WHERE id_usuario = :id', [':h' => $hash, ':id' => $idUsuario]);
}
// ---- CU01: fin ----
