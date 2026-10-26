<?php
/**
 * CU02 - Gestionar usuarios
 * Paquete: seguridad  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF01 / Rol Administrador
 *
 * Registrar, consultar, modificar, activar y desactivar las cuentas de usuario del personal y de
 * los pacientes, asignándoles un rol.
 *
 * Actores ........: Administrador
 * Permiso ........: usuario.gestionar
 * Tablas .........: usuario, rol, bitacora
 *
 * Precondición:
 *     El administrador inició sesión (CU01).
 *
 * Flujo principal:
 *     1. El administrador ingresa a la opción «Usuarios».
 *     2. El sistema lista los usuarios con su rol y estado, con búsqueda por nombre de usuario o correo.
 *     3. El administrador elige «Nuevo usuario» e introduce nombre de usuario, correo, contraseña inicial y rol; si el rol es Médico o Paciente, vincula la cuenta con el registro correspondiente.
 *     4. El sistema valida que el usuario y el correo no existan, genera el hash con password_hash() y guarda la cuenta.
 *     5. Para modificar, el administrador selecciona un usuario, cambia sus datos o su rol y guarda.
 *     6. Para desactivar o activar, el administrador cambia el estado; la cuenta no se elimina.
 *     7. El sistema registra cada operación en la bitácora y muestra un mensaje de confirmación.
 *
 * Excepciones / reglas que debe controlar:
 *     - Nombre de usuario o correo ya registrado.
 *     - Contraseña que no cumple la longitud mínima (8 caracteres, letras y números).
 *     - Intento de desactivar la propia cuenta del administrador en sesión.
 *
 * Postcondición:
 *     La cuenta queda registrada o actualizada con su rol; el cambio queda en la bitácora.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu02GestionarUsuarios
{
    /** GET /api/v1/seguridad/usuarios — Listar usuarios */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return Cu02Usuarios::listar($p);
    }
    /** POST /api/v1/seguridad/usuarios — Registrar usuario */
    public static function crear(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return Cu02Usuarios::crear($p);
    }
    /** PUT /api/v1/seguridad/usuarios/{id} — Modificar usuario */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return Cu02Usuarios::modificar($p);
    }
    /** PATCH /api/v1/seguridad/usuarios/{id}/estado — Activar o desactivar usuario */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return Cu02Usuarios::cambiarEstado($p);
    }
}

// ---- CU02: inicio ----
use App\Nucleo\{BaseDatos, Correo, ErrorApi};
use function App\Paquetes\Seguridad\Schemas\{
    validar_listar_usuarios, validar_crear_usuario, validar_modificar_usuario, validar_cambiar_estado_usuario
};
use function App\Paquetes\Seguridad\Politicas\{generarContrasena, ipCliente, validarNoAutoDesactivacion};
use function App\Paquetes\Seguridad\Repository\{
    listarUsuarios, crearUsuario, modificarUsuario, cambiarEstadoUsuarioConIntentos, registrarBitacora,
    buscarUsuarioPorId, buscarPacienteSinCuenta, buscarMedicoSinCuenta, vincularPaciente, vincularMedico
};

/**
 * Lógica de CU02 (gestión de usuarios: alta, consulta, modificación, cambio de estado).
 */
final class Cu02Usuarios
{
    public static function listar(Peticion $p): array
    {
        $s = Sesion::usuario();
        $d = validar_listar_usuarios([
            'filtro' => $p->consulta('filtro') ?: null,
            'rol' => $p->consulta('rol') ?: null,
            'estado' => $p->consulta('estado') ?: null,
        ]);

        $usuarios = listarUsuarios($d['filtro'], $d['rol'], $d['estado']);

        return [
            'usuarios' => array_map(static fn ($u) => [
                'id_usuario' => (int) $u['id_usuario'],
                'username' => $u['username'],
                'correo' => $u['correo'],
                'rol' => $u['rol'],
                'estado' => $u['estado'],
                'puede_activar' => $u['estado'] !== 'activo',
                'puede_desactivar' => $u['estado'] === 'activo' && (int) $u['id_usuario'] !== $s['id_usuario'],
                'puede_desbloquear' => $u['estado'] === 'bloqueado',
            ], $usuarios),
        ];
    }

    public static function crear(Peticion $p): array
    {
        $s = Sesion::usuario();
        $d = validar_crear_usuario($p->cuerpo());
        $ip = ipCliente();

        $r = BaseDatos::transaccion(static function () use ($s, $d, $ip): array {
            // Validar uniqueness de usuario y correo
            $existe = BaseDatos::fila(
                'SELECT id_usuario FROM usuario WHERE lower(username) = lower(:u) OR lower(correo) = lower(:c)',
                [':u' => $d['username'], ':c' => $d['correo']]
            );
            if ($existe) {
                throw new ErrorApi(409, 'El usuario o correo ya está registrado.');
            }

            // Generar o usar contraseña proporcionada
            $contrasena = $d['contrasena'] ?: generarContrasena();
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Buscar el rol por nombre
            $rol = BaseDatos::fila(
                'SELECT id_rol FROM rol WHERE lower(nombre) = lower(:n)',
                [':n' => $d['rol']]
            );
            if (!$rol) {
                throw new ErrorApi(400, 'Datos inválidos.', ['rol' => 'Rol no válido.']);
            }
            $idRol = (int) $rol['id_rol'];

            // Crear usuario
            $idUsuario = crearUsuario($idRol, $d['username'], $d['correo'], $hash);

            // Vincular con paciente o médico si corresponde
            if ($d['id_paciente']) {
                $p = buscarPacienteSinCuenta($d['id_paciente']);
                if ($p) {
                    vincularPaciente($d['id_paciente'], $idUsuario);
                }
            } elseif ($d['id_medico']) {
                $m = buscarMedicoSinCuenta($d['id_medico']);
                if ($m) {
                    vincularMedico($d['id_medico'], $idUsuario);
                }
            }

            registrarBitacora($s['id_usuario'], 'USUARIO_CREAR', $idUsuario, 'Usuario creado: ' . $d['username'], $ip);

            return [
                'id_usuario' => $idUsuario,
                'username' => $d['username'],
                'correo' => $d['correo'],
                'contrasena_generada' => !$d['contrasena'],
                'contrasena' => $contrasena,
            ];
        }, $s['id_usuario']);

        // Enviar contraseña por correo si fue generada
        if ($r['contrasena_generada']) {
            $html = self::plantillaContrasena($d['username'], $r['contrasena']);
            Correo::enviar($d['correo'], 'Cuenta creada - Clínica Vida Salud', $html);
        }

        return [
            'mensaje' => 'Usuario creado correctamente.' . ($r['contrasena_generada'] ? ' Se envió la contraseña al correo.' : ''),
            'usuario' => [
                'id_usuario' => $r['id_usuario'],
                'username' => $r['username'],
                'correo' => $r['correo'],
            ],
        ];
    }

    public static function modificar(Peticion $p): array
    {
        $s = Sesion::usuario();
        $id = (int) $p->param('id');
        $d = validar_modificar_usuario($p->cuerpo());
        $ip = ipCliente();

        $r = BaseDatos::transaccion(static function () use ($s, $id, $d, $ip): array {
            $u = buscarUsuarioPorId($id, true);
            if (!$u) {
                throw new ErrorApi(404, 'Usuario no encontrado.');
            }

            // Validar uniqueness: otro usuario con el mismo username o correo
            $existe = BaseDatos::fila(
                'SELECT id_usuario FROM usuario WHERE (lower(username) = lower(:u) OR lower(correo) = lower(:c)) AND id_usuario != :id',
                [':u' => $d['username'], ':c' => $d['correo'], ':id' => $id]
            );
            if ($existe) {
                throw new ErrorApi(409, 'El usuario o correo ya está registrado.');
            }

            // Buscar el rol por nombre
            $rol = BaseDatos::fila(
                'SELECT id_rol FROM rol WHERE lower(nombre) = lower(:n)',
                [':n' => $d['rol']]
            );
            if (!$rol) {
                throw new ErrorApi(400, 'Datos inválidos.', ['rol' => 'Rol no válido.']);
            }
            $idRol = (int) $rol['id_rol'];

            // Registrar cambios en detalle
            $cambios = [];
            if ($u['username'] !== $d['username']) {
                $cambios[] = "Usuario: {$u['username']} → {$d['username']}";
            }
            if ($u['correo'] !== $d['correo']) {
                $cambios[] = "Correo: {$u['correo']} → {$d['correo']}";
            }
            if ((int) $u['id_rol'] !== $idRol) {
                $cambios[] = "Rol: {$u['rol']} → {$d['rol']}";
            }

            if (!$cambios) {
                throw new ErrorApi(400, 'No hay cambios para guardar.');
            }

            modificarUsuario($id, $d['username'], $d['correo'], $idRol);
            registrarBitacora($s['id_usuario'], 'USUARIO_MODIFICAR', $id, implode('; ', $cambios), $ip);

            return [
                'id_usuario' => $id,
                'username' => $d['username'],
                'correo' => $d['correo'],
                'rol' => $d['rol'],
            ];
        }, $s['id_usuario']);

        return [
            'mensaje' => 'Usuario modificado correctamente.',
            'usuario' => $r,
        ];
    }

    public static function cambiarEstado(Peticion $p): array
    {
        $s = Sesion::usuario();
        $id = (int) $p->param('id');
        $d = validar_cambiar_estado_usuario($p->cuerpo());
        $ip = ipCliente();

        validarNoAutoDesactivacion($id, $d['estado']);

        $r = BaseDatos::transaccion(static function () use ($s, $id, $d, $ip): array {
            $u = buscarUsuarioPorId($id, true);
            if (!$u) {
                throw new ErrorApi(404, 'Usuario no encontrado.');
            }

            if ($u['estado'] === $d['estado']) {
                throw new ErrorApi(400, 'El usuario ya está en ese estado.');
            }

            cambiarEstadoUsuarioConIntentos($id, $d['estado']);

            $mensaje = match ($d['estado']) {
                'activo' => 'Desbloqueado',
                'inactivo' => 'Desactivado',
                'bloqueado' => 'Bloqueado',
            };
            registrarBitacora($s['id_usuario'], 'USUARIO_ESTADO', $id, "Usuario {$mensaje}: {$u['username']}", $ip);

            return [
                'id_usuario' => $id,
                'username' => $u['username'],
                'estado' => $d['estado'],
            ];
        }, $s['id_usuario']);

        return [
            'mensaje' => 'Estado del usuario actualizado correctamente.',
            'usuario' => $r,
        ];
    }

    /** Plantilla HTML para enviar contraseña generada. */
    private static function plantillaContrasena(string $username, string $contrasena): string
    {
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $contrasena = htmlspecialchars($contrasena, ENT_QUOTES, 'UTF-8');
        return '<div style="font-family:Segoe UI,Arial,sans-serif;background:#F1F5F4;padding:24px;color:#1F2D2B">'
            . '<div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #D5E0DE;border-radius:8px;overflow:hidden">'
            . '<div style="background:#134E4A;color:#fff;padding:14px 20px;font-weight:700">&#10010; Clínica Vida Salud</div>'
            . '<div style="padding:20px"><h2 style="margin:0 0 14px;font-size:18px;color:#134E4A">Su cuenta fue creada</h2>'
            . '<p style="margin:0 0 14px">Hola ' . $username . ',</p>'
            . '<p style="margin:0 0 14px">Su cuenta en la Clínica Vida Salud ha sido creada. Su contraseña temporal es:</p>'
            . '<p style="margin:14px 0;text-align:center"><strong style="font-size:20px;letter-spacing:1px;font-family:Consolas,monospace">' . $contrasena . '</strong></p>'
            . '<p style="margin:0 0 14px">Al iniciar sesión el sistema le pedirá cambiarla por una contraseña nueva.</p>'
            . '<p style="margin:18px 0 0;font-size:12px;color:#5B6B69">Mensaje enviado por el sistema de gestión de citas. No responda a este correo.</p>'
            . '</div></div></div>';
    }
}
// ---- CU02: fin ----
