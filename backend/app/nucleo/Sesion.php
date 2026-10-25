<?php
/**
 * Sesion: sesión PHP del usuario y control de acceso por permisos (RN12).
 *
 * El inicio de sesión (CU01) guarda en $_SESSION: id_usuario, username, rol y permisos.
 * Cada caso de uso protegido llama a Sesion::requerir('codigo.permiso').
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('clinica_sesion');
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !Config::esDesarrollo(),
            ]);
            session_start();
        }
    }

    /** Guarda el usuario autenticado (lo usará CU01). */
    public static function establecer(array $usuario, array $permisos): void
    {
        self::iniciar();
        session_regenerate_id(true); // evita la fijación de sesión
        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'username'   => $usuario['username'],
            'rol'        => $usuario['rol'],
            'permisos'   => array_values($permisos),
        ];
    }

    public static function usuario(): ?array
    {
        self::iniciar();
        return $_SESSION['usuario'] ?? null;
    }

    /** Exige sesión válida y, opcionalmente, un permiso. Devuelve el usuario actual. */
    public static function requerir(?string $permiso = null): array
    {
        $u = self::usuario();
        if ($u === null) {
            throw new ErrorApi(401, 'Debe iniciar sesión.');
        }
        if ($permiso !== null && !in_array($permiso, $u['permisos'], true)) {
            throw new ErrorApi(403, 'No tiene permiso para esta operación.');
        }
        return $u;
    }

    public static function cerrar(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
    }
}
