<?php
/**
 * CU01 - Iniciar y cerrar sesión
 * Paquete: seguridad  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF01
 *
 * Permitir que un usuario autorizado ingrese al sistema con su nombre de usuario o correo y su
 * contraseña, acceda solo a las opciones de su rol y cierre su sesión de forma segura.
 *
 * Actores ........: Administrador, Recepcionista, Médico, Paciente
 * Permiso ........: ninguno (público)
 * Tablas .........: usuario, rol, bitacora
 *
 * Precondición:
 *     El usuario tiene una cuenta registrada con estado «activo».
 *
 * Flujo principal:
 *     1. El usuario ingresa a la página de inicio de sesión.
 *     2. Introduce su nombre de usuario o correo electrónico y su contraseña.
 *     3. El sistema valida los campos en el cliente (JavaScript) y en el servidor (PHP).
 *     4. El sistema busca la cuenta y verifica la contraseña con password_verify() contra el hash almacenado.
 *     5. El sistema regenera el identificador de sesión (session_regenerate_id), guarda el usuario y su rol en $_SESSION y registra el último acceso en la bitácora.
 *     6. El sistema muestra el menú principal con las opciones permitidas para el rol.
 *     7. Al terminar, el usuario selecciona «Cerrar sesión»; el sistema destruye la sesión y lo redirige al inicio de sesión.
 *
 * Excepciones / reglas que debe controlar:
 *     - Usuario o contraseña incorrectos: se muestra un mensaje genérico, sin indicar cuál dato falló.
 *     - Cuenta inactiva o bloqueada (tras 5 intentos fallidos): se informa que el acceso no está permitido.
 *     - Acceso a una página interna sin sesión válida: el sistema redirige al inicio de sesión.
 *
 * Postcondición:
 *     El usuario accede únicamente a las funcionalidades de su rol; el acceso queda registrado en la bitácora.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu01IniciarSesion
{
    /** POST /api/v1/seguridad/sesion — Iniciar sesión */
    public static function iniciar(Peticion $p): mixed
    {
        // CU01 no exige sesión previa
        return Cu01Sesion::iniciar($p);
    }
    /** DELETE /api/v1/seguridad/sesion — Cerrar sesión */
    public static function cerrar(Peticion $p): mixed
    {
        // CU01 no exige sesión previa
        return Cu01Sesion::cerrar();
    }
    /** GET /api/v1/seguridad/sesion — Usuario de la sesión actual */
    public static function actual(Peticion $p): mixed
    {
        // CU01 no exige sesión previa
        return Cu01Sesion::actual();
    }

    // ---- CU01: inicio ----
    /** POST /api/v1/seguridad/recuperar — Recuperar contraseña (público) */
    public static function recuperar(Peticion $p): mixed
    {
        return Cu01Sesion::recuperar($p);
    }

    /** PUT /api/v1/seguridad/contrasena — Cambiar contraseña (con sesión) */
    public static function cambiarContrasena(Peticion $p): mixed
    {
        return Cu01Sesion::cambiarContrasena($p);
    }
    // ---- CU01: fin ----
}

// ---- CU01: inicio ----
use App\Nucleo\{BaseDatos, Correo, ErrorApi};
use function App\Paquetes\Seguridad\Schemas\{validar_login, validar_recuperar, validar_cambio_contrasena};
use function App\Paquetes\Seguridad\Politicas\{
    esBloqueoPorIntentos, generarContrasenaTemporal, ipCliente, mensajeBloqueo, mensajeCredenciales,
    minutosBloqueo, verificarCuentaHabilitada
};
use function App\Paquetes\Seguridad\Repository\{
    actualizarContrasena, buscarUsuarioPorCorreo, buscarUsuarioPorId, buscarUsuarioPorLogin, cambiarEstadoUsuario,
    debeCambiarContrasena, permisosDelRol, registrarAccesoCorrecto, registrarBitacora, restablecerContrasena,
    sumarIntentoFallido, ultimoBloqueoIntentos
};
use const App\Paquetes\Seguridad\Politicas\INTENTOS_PERMITIDOS;

/**
 * Lógica de CU01 (inicio y cierre de sesión, recuperación y cambio de contraseña).
 * Va en una clase aparte para declarar sus propios use sin modificar el encabezado del archivo.
 *
 * Bloqueo progresivo: al tercer intento fallido la cuenta pasa a 'bloqueado' y se registra
 * BLOQUEO_INTENTOS (su fecha marca el inicio). Duración: min(intentos - 2, MAX_MINUTOS_BLOQUEO).
 * Cumplido el tiempo se desbloquea sola sin reiniciar intentos_fallidos.
 */
final class Cu01Sesion
{
    /** Hash ficticio: iguala el tiempo de respuesta cuando el usuario no existe. */
    private const HASH_FICTICIO = '$2y$10$3qsKhR371SxDjSuJ5DRPue7Sk3pBhH7TwaY7XHgvoDhdDF15QBmvS';

    private const MENSAJE_RECUPERAR = 'Si el correo está registrado, recibirá una contraseña temporal.';

    public static function iniciar(Peticion $p): array
    {
        $d = validar_login($p->cuerpo());
        $ip = ipCliente();

        // Los fallos se confirman en la base antes de responder con el error
        $r = BaseDatos::transaccion(static function () use ($d, $ip): array {
            $u = buscarUsuarioPorLogin($d['login'], true);
            if ($u === null) {
                password_verify($d['contrasena'], self::HASH_FICTICIO);
                registrarBitacora(null, 'LOGIN_FALLIDO', null, 'Usuario inexistente: ' . mb_substr($d['login'], 0, 100), $ip);
                return ['inexistente' => true];
            }
            $id = (int) $u['id_usuario'];

            try {
                verificarCuentaHabilitada($u);
            } catch (ErrorApi $e) {
                registrarBitacora($id, 'LOGIN_FALLIDO', $id, 'Intento con cuenta inactiva', $ip);
                return ['error' => $e];
            }

            $detalle = '';
            if ($u['estado'] === 'bloqueado') {
                $bloqueo = ultimoBloqueoIntentos($id, minutosBloqueo((int) $u['intentos_fallidos']));
                if (!esBloqueoPorIntentos($u, $bloqueo)) {
                    registrarBitacora($id, 'LOGIN_FALLIDO', $id, 'Intento con cuenta bloqueada por el administrador', $ip);
                    return ['error' => new ErrorApi(403, 'La cuenta está bloqueada. El acceso no está permitido; comuníquese con el administrador.')];
                }
                $segundos = (int) $bloqueo['segundos_restantes'];
                if ($segundos > 0) {
                    registrarBitacora($id, 'LOGIN_FALLIDO', $id, 'Intento durante el bloqueo', $ip);
                    return ['error' => new ErrorApi(423, mensajeBloqueo((int) ceil($segundos / 60)))];
                }
                // El bloqueo venció: se desbloquea sola y continúa el login (intentos_fallidos se conserva)
                cambiarEstadoUsuario($id, 'activo');
                $detalle = ' (desbloqueo automático al vencer el bloqueo)';
            }

            if (!password_verify($d['contrasena'], $u['password_hash'])) {
                $intentos = sumarIntentoFallido($id);
                registrarBitacora($id, 'LOGIN_FALLIDO', $id, "Contraseña incorrecta, intento {$intentos}{$detalle}", $ip);
                if ($intentos >= INTENTOS_PERMITIDOS) {
                    $minutos = minutosBloqueo($intentos);
                    cambiarEstadoUsuario($id, 'bloqueado');
                    $numero = $intentos - (INTENTOS_PERMITIDOS - 1);
                    registrarBitacora($id, 'BLOQUEO_INTENTOS', $id, "Bloqueo n.º {$numero} por {$minutos} min ({$intentos} intentos fallidos)", $ip);
                    return ['error' => new ErrorApi(423, mensajeBloqueo($minutos))];
                }
                return ['error' => new ErrorApi(401, mensajeCredenciales($intentos))];
            }

            registrarAccesoCorrecto($id);
            registrarBitacora($id, 'LOGIN_OK', $id, 'Inicio de sesión correcto' . $detalle, $ip);
            return [
                'usuario'  => $u,
                'debe'     => debeCambiarContrasena($id),
                'permisos' => permisosDelRol((int) $u['id_rol']),
            ];
        });

        if (isset($r['inexistente'])) {
            self::falloUsuarioInexistente($d['login']);
        }
        if (isset($r['error'])) {
            throw $r['error'];
        }

        // Mientras deba cambiar la contraseña, la sesión no lleva permisos: el servidor rechaza el resto
        Sesion::establecer($r['usuario'], $r['debe'] ? [] : $r['permisos']);
        unset($_SESSION['cu01_inexistentes']);
        $_SESSION['usuario']['correo'] = $r['usuario']['correo'];
        $_SESSION['usuario']['debe_cambiar_contrasena'] = $r['debe'];
        return self::datosSesion() + ['ultimo_acceso_anterior' => $r['usuario']['ultimo_acceso']];
    }

    public static function cerrar(): null
    {
        $u = Sesion::usuario();
        if ($u !== null) {
            registrarBitacora($u['id_usuario'], 'LOGOUT', $u['id_usuario'], 'Cierre de sesión', ipCliente());
        }
        $cookie = session_get_cookie_params();
        Sesion::cerrar();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $cookie['path'],
            'domain'   => $cookie['domain'],
            'secure'   => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'],
        ]);
        return null;
    }

    public static function actual(): array
    {
        $s = Sesion::requerir();
        // Se revisa la cuenta en la base: si fue desactivada o bloqueada, la sesión deja de valer
        $u = buscarUsuarioPorId($s['id_usuario']);
        if ($u === null || $u['estado'] !== 'activo' || $u['rol_estado'] !== 'activo') {
            Sesion::cerrar();
            throw new ErrorApi(401, 'La sesión ya no es válida. Inicie sesión nuevamente.');
        }
        $debe = debeCambiarContrasena((int) $u['id_usuario']);
        $_SESSION['usuario']['rol'] = $u['rol'];
        $_SESSION['usuario']['correo'] = $u['correo'];
        $_SESSION['usuario']['permisos'] = $debe ? [] : permisosDelRol((int) $u['id_rol']);
        $_SESSION['usuario']['debe_cambiar_contrasena'] = $debe;
        return self::datosSesion();
    }

    public static function recuperar(Peticion $p): array
    {
        $d = validar_recuperar($p->cuerpo());
        $ip = ipCliente();

        $r = BaseDatos::transaccion(static function () use ($d, $ip): ?array {
            $u = buscarUsuarioPorCorreo($d['correo'], true);
            if ($u === null || $u['rol_estado'] !== 'activo') {
                return null;
            }
            $id = (int) $u['id_usuario'];
            $habilitada = $u['estado'] === 'activo'
                || esBloqueoPorIntentos($u, ultimoBloqueoIntentos($id, 1));
            if (!$habilitada) {
                return null;
            }
            $temporal = generarContrasenaTemporal();
            restablecerContrasena($id, password_hash($temporal, PASSWORD_DEFAULT));
            registrarBitacora($id, 'RECUPERACION_CONTRASENA', $id, 'Contraseña temporal enviada al correo', $ip);
            return ['usuario' => $u, 'temporal' => $temporal];
        });

        if ($r !== null) {
            $u = $r['usuario'];
            $html = self::plantillaCorreo('Recuperación de contraseña', [
                'Hola ' . self::e($u['username']) . ',',
                'Recibimos una solicitud para recuperar el acceso a su cuenta. Su contraseña temporal es:',
                '<strong style="font-size:20px;letter-spacing:1px;font-family:Consolas,monospace">' . self::e($r['temporal']) . '</strong>',
                'Al iniciar sesión el sistema le pedirá cambiarla por una contraseña nueva.',
                'Si usted no hizo esta solicitud, inicie sesión con la contraseña temporal y cámbiela.',
            ]);
            Correo::enviar($u['correo'], 'Contraseña temporal - Clínica Vida Salud', $html);
        }
        return ['mensaje' => self::MENSAJE_RECUPERAR];
    }

    public static function cambiarContrasena(Peticion $p): array
    {
        $s = Sesion::requerir();
        $d = validar_cambio_contrasena($p->cuerpo());
        $ip = ipCliente();

        $u = BaseDatos::transaccion(static function () use ($s, $d, $ip): array {
            $u = buscarUsuarioPorId($s['id_usuario'], true);
            if ($u === null) {
                throw new ErrorApi(401, 'Debe iniciar sesión.');
            }
            if (!password_verify($d['actual'], $u['password_hash'])) {
                throw new ErrorApi(400, 'Datos inválidos.', ['actual' => 'La contraseña actual es incorrecta.']);
            }
            if (password_verify($d['nueva'], $u['password_hash'])) {
                throw new ErrorApi(400, 'Datos inválidos.', ['nueva' => 'La nueva contraseña debe ser distinta de la actual.']);
            }
            actualizarContrasena((int) $u['id_usuario'], password_hash($d['nueva'], PASSWORD_DEFAULT));
            registrarBitacora((int) $u['id_usuario'], 'CAMBIO_CONTRASENA', (int) $u['id_usuario'], 'Cambio de contraseña', $ip);
            return $u;
        }, $s['id_usuario']);

        // Con la contraseña cambiada se habilitan los permisos del rol
        $_SESSION['usuario']['permisos'] = permisosDelRol((int) $u['id_rol']);
        $_SESSION['usuario']['debe_cambiar_contrasena'] = false;

        $ahora = new \DateTimeImmutable('now', new \DateTimeZone('America/La_Paz'));
        $html = self::plantillaCorreo('Su contraseña fue cambiada', [
            'Hola ' . self::e($u['username']) . ',',
            'La contraseña de su cuenta se cambió correctamente.',
            'Fecha: ' . $ahora->format('d/m/Y') . '<br>Hora: ' . $ahora->format('H:i') . '<br>Dirección IP: ' . self::e($ip),
            'Si usted no hizo este cambio, use «¿Olvidaste tu contraseña?» en la pantalla de inicio de sesión y avise al administrador.',
        ]);
        Correo::enviar($u['correo'], 'Aviso: cambio de contraseña - Clínica Vida Salud', $html);

        return ['mensaje' => 'La contraseña se cambió correctamente.', 'usuario' => self::datosSesion()];
    }

    /**
     * Usuario inexistente: mismos mensajes que una cuenta real (contador en la sesión del navegador)
     * para no revelar qué usuarios existen.
     */
    private static function falloUsuarioInexistente(string $login): never
    {
        Sesion::iniciar();
        $clave = mb_strtolower($login);
        $intentos = (int) ($_SESSION['cu01_inexistentes'][$clave] ?? 0) + 1;
        $_SESSION['cu01_inexistentes'][$clave] = $intentos;
        if ($intentos >= INTENTOS_PERMITIDOS) {
            throw new ErrorApi(423, mensajeBloqueo(minutosBloqueo($intentos)));
        }
        throw new ErrorApi(401, mensajeCredenciales($intentos));
    }

    /** Datos del usuario de la sesión que recibe el frontend. */
    private static function datosSesion(): array
    {
        $u = $_SESSION['usuario'];
        return [
            'id_usuario'              => $u['id_usuario'],
            'username'                => $u['username'],
            'correo'                  => $u['correo'] ?? null,
            'rol'                     => $u['rol'],
            'permisos'                => $u['permisos'],
            'debe_cambiar_contrasena' => (bool) ($u['debe_cambiar_contrasena'] ?? false),
        ];
    }

    private static function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    /** Correo HTML simple con los colores del sistema. Los párrafos ya vienen escapados. */
    private static function plantillaCorreo(string $titulo, array $parrafos): string
    {
        $cuerpo = implode('', array_map(static fn ($t) => '<p style="margin:0 0 14px">' . $t . '</p>', $parrafos));
        return '<div style="font-family:Segoe UI,Arial,sans-serif;background:#F1F5F4;padding:24px;color:#1F2D2B">'
            . '<div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #D5E0DE;border-radius:8px;overflow:hidden">'
            . '<div style="background:#134E4A;color:#fff;padding:14px 20px;font-weight:700">&#10010; Clínica Vida Salud</div>'
            . '<div style="padding:20px"><h2 style="margin:0 0 14px;font-size:18px;color:#134E4A">' . self::e($titulo) . '</h2>'
            . $cuerpo
            . '<p style="margin:18px 0 0;font-size:12px;color:#5B6B69">Mensaje enviado por el sistema de gestión de citas. No responda a este correo.</p>'
            . '</div></div></div>';
    }
}
// ---- CU01: fin ----
