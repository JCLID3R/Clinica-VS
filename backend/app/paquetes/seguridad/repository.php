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

// ---- CU02: inicio ----
/** Columnas del usuario que usa CU02 (para listar y editar). */
const SQL_USUARIO_CU02 = "SELECT u.id_usuario, u.id_rol, u.username, u.correo, u.estado,
                                 u.intentos_fallidos, u.ultimo_acceso, r.nombre AS rol, r.estado AS rol_estado
                            FROM usuario u
                            JOIN rol r ON r.id_rol = u.id_rol";

/** Listar usuarios con búsqueda (por usuario o correo) y filtros (por rol y estado). */
function listarUsuarios(?string $filtro, ?int $idRol, ?string $estado): array
{
    $sql = SQL_USUARIO_CU02 . ' WHERE 1=1';
    $params = [];

    if ($filtro) {
        $sql .= ' AND (lower(u.username) LIKE lower(:filtro) OR lower(u.correo) LIKE lower(:filtro))';
        $params[':filtro'] = '%' . $filtro . '%';
    }
    if ($idRol !== null) {
        $sql .= ' AND u.id_rol = :rol';
        $params[':rol'] = $idRol;
    }
    if ($estado !== null) {
        $sql .= ' AND u.estado = :estado';
        $params[':estado'] = $estado;
    }

    $sql .= ' ORDER BY u.username ASC';
    return BaseDatos::consultar($sql, $params);
}

/** Crear usuario: inserta en tabla usuario y devuelve el id. */
function crearUsuario(int $idRol, string $username, string $correo, string $passwordHash): int
{
    BaseDatos::ejecutar(
        'INSERT INTO usuario (id_rol, username, correo, password_hash, estado)
         VALUES (:rol, :user, :mail, :pass, :est)',
        [':rol' => $idRol, ':user' => $username, ':mail' => $correo, ':pass' => $passwordHash, ':est' => 'activo']
    );
    $fila = BaseDatos::fila('SELECT LASTVAL() AS id');
    return (int) $fila['id'];
}

/** Modificar usuario: username, correo, rol. */
function modificarUsuario(int $idUsuario, string $username, string $correo, int $idRol): void
{
    BaseDatos::ejecutar(
        'UPDATE usuario SET username = :user, correo = :mail, id_rol = :rol WHERE id_usuario = :id',
        [':user' => $username, ':mail' => $correo, ':rol' => $idRol, ':id' => $idUsuario]
    );
}

/** Cambiar estado usuario (activo, inactivo, bloqueado). Si es desbloqueo, reinicia intentos. */
function cambiarEstadoUsuarioConIntentos(int $idUsuario, string $estado): void
{
    if ($estado === 'activo') {
        BaseDatos::ejecutar(
            'UPDATE usuario SET estado = :e, intentos_fallidos = 0 WHERE id_usuario = :id',
            [':e' => $estado, ':id' => $idUsuario]
        );
    } else {
        BaseDatos::ejecutar(
            'UPDATE usuario SET estado = :e WHERE id_usuario = :id',
            [':e' => $estado, ':id' => $idUsuario]
        );
    }
}

/** Buscar paciente sin cuenta (id_usuario es NULL). */
function buscarPacienteSinCuenta(int $idPaciente): ?array
{
    return BaseDatos::fila(
        'SELECT id_paciente, ci, nombres, apellidos FROM paciente WHERE id_paciente = :id AND id_usuario IS NULL',
        [':id' => $idPaciente]
    );
}

/** Buscar médico sin cuenta (id_usuario es NULL). */
function buscarMedicoSinCuenta(int $idMedico): ?array
{
    return BaseDatos::fila(
        'SELECT id_medico, registro_profesional, nombres, apellidos FROM medico WHERE id_medico = :id AND id_usuario IS NULL',
        [':id' => $idMedico]
    );
}

/** Vincular usuario con paciente. */
function vincularPaciente(int $idPaciente, int $idUsuario): void
{
    BaseDatos::ejecutar(
        'UPDATE paciente SET id_usuario = :user WHERE id_paciente = :id',
        [':user' => $idUsuario, ':id' => $idPaciente]
    );
}

/** Vincular usuario con médico. */
function vincularMedico(int $idMedico, int $idUsuario): void
{
    BaseDatos::ejecutar(
        'UPDATE medico SET id_usuario = :user WHERE id_medico = :id',
        [':user' => $idUsuario, ':id' => $idMedico]
    );
}
// ---- CU02: fin ----
