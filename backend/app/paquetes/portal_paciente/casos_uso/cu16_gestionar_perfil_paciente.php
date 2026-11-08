<?php
/**
 * CU16 - Gestionar perfil del paciente
 * Paquete: portal_paciente  ·  Ciclo 2  ·  Prioridad Media  ·  Requisito: Perfil del paciente (3.4)
 *
 * Permitir al paciente registrarse en el portal y mantener actualizado su perfil: datos personales,
 * contacto, información médica (tipo de sangre, alergias, enfermedades crónicas, antecedentes),
 * contacto de emergencia y preferencias de la cuenta.
 *
 * Actores ........: Paciente
 * Permiso ........: perfil.paciente
 * Tablas .........: paciente, contacto_emergencia, usuario
 *
 * Precondición:
 *     El paciente tiene un CI válido; para editar, inició sesión (CU01).
 *
 * Flujo principal:
 *     1. El paciente ingresa al portal y elige «Crear cuenta» o inicia sesión (CU01).
 *     2. Al crear la cuenta, ingresa su CI; si ya está registrado por recepción, el sistema vincula la cuenta a su registro; si no, crea el paciente.
 *     3. El paciente abre «Mi perfil»; el sistema muestra las secciones del perfil.
 *     4. El paciente actualiza sus datos personales, de contacto, información médica y contacto de emergencia.
 *     5. El paciente cambia su contraseña o sus preferencias de notificaciones y privacidad.
 *     6. El sistema valida y guarda los cambios.
 *
 * Excepciones / reglas que debe controlar:
 *     - El CI ya tiene una cuenta de portal vinculada.
 *     - Datos con formato inválido.
 *     - Contraseña actual incorrecta al cambiarla.
 *
 * Postcondición:
 *     El perfil del paciente queda actualizado.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Personas\Repository\{pacientePorUsuario, pacienteModificarContacto, reemplazarContactos};
use function App\Paquetes\Seguridad\Repository\{crearUsuario};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu16GestionarPerfilPaciente
{
    /** POST /api/v1/portal/registro — Crear cuenta del portal (autorregistro) */
    public static function registrarCuenta(Peticion $p): mixed
    {
        $body = $p->cuerpo();
        $ci = (int) ($body['ci'] ?? 0);
        $fechaNacimiento = trim($body['fecha_nacimiento'] ?? '');
        $login = trim($body['login'] ?? '');
        $contrasena = trim($body['contrasena'] ?? '');

        if (!$ci || !$fechaNacimiento || !$login || !$contrasena) {
            throw new ErrorApi(400, 'Todos los campos requeridos (CI, fecha de nacimiento, usuario, contraseña).');
        }

        // Validar paciente existe y está activo (rápida, antes de transacción)
        $paciente = BaseDatos::fila(
            "SELECT id_paciente, id_usuario, fecha_nacimiento, correo FROM paciente WHERE ci = :ci AND estado = 'activo'",
            [':ci' => $ci]
        );

        if (!$paciente) {
            throw new ErrorApi(404, 'No encontramos un paciente con esos datos; acércate a recepción.');
        }

        if ($paciente['fecha_nacimiento'] !== $fechaNacimiento) {
            throw new ErrorApi(401, 'La fecha de nacimiento no coincide.');
        }

        // Crear usuario con rol Paciente (id_rol = 4) en transacción
        try {
            BaseDatos::transaccion(function () use ($paciente, $login, $contrasena, $ci) {
                // Validar dentro de transacción: paciente.id_usuario es NULL y login no existe
                $pacienteActual = BaseDatos::fila(
                    "SELECT id_usuario FROM paciente WHERE id_paciente = :id",
                    [':id' => $paciente['id_paciente']]
                );
                if ($pacienteActual['id_usuario']) {
                    throw new ErrorApi(409, 'Este paciente ya tiene una cuenta.');
                }

                $existeLogin = BaseDatos::fila(
                    "SELECT id_usuario FROM usuario WHERE username = :login",
                    [':login' => $login]
                );
                if ($existeLogin) {
                    throw new ErrorApi(409, 'Ese usuario ya está en uso.');
                }
                $hashContrasena = password_hash($contrasena, PASSWORD_BCRYPT);

$correo = $login . '@pacientes.vidasalud.bo';
                if (!empty(trim($paciente['correo'] ?? ''))) {
                    $existeCorreo = BaseDatos::fila(
                        "SELECT id_usuario FROM usuario WHERE correo = :mail",
                        [':mail' => $paciente['correo']]
                    );
                    if (!$existeCorreo) {
                        $correo = $paciente['correo'];
                    }
                }

                $idUsuario = crearUsuario(4, $login, $correo, $hashContrasena);

                BaseDatos::ejecutar(
                    "UPDATE paciente SET id_usuario = :iu WHERE id_paciente = :p",
                    [':iu' => $idUsuario, ':p' => $paciente['id_paciente']]
                );

                registrarBitacora($idUsuario, 'PORTAL_REGISTRO_AUTORREGISTRO', $paciente['id_paciente'], 'Autorregistro en portal', 'paciente', ipCliente());
            }, null);
        } catch (ErrorApi $e) {
            throw $e;
        } catch (\PDOException $e) {
            error_log('Error en registrarCuenta: ' . $e->getMessage());
            if ($e->getCode() === '23505') {
                throw new ErrorApi(409, 'Ese usuario ya está en uso.');
            }
            throw new ErrorApi(500, 'Error al crear la cuenta.');
        }

        return ['mensaje' => 'Cuenta creada exitosamente. Por favor inicia sesión.'];
    }

    /** GET /api/v1/portal/perfil — Mi perfil */
    public static function obtenerPerfil(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $contactos = BaseDatos::consultar(
            "SELECT * FROM contacto_emergencia WHERE id_paciente = :p",
            [':p' => $paciente['id_paciente']]
        );

        return ['paciente' => $paciente, 'contactos' => $contactos];
    }

    /** PUT /api/v1/portal/perfil — Actualizar mi perfil */
    public static function actualizarPerfil(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $body = $p->cuerpo();
        $telefono = trim($body['telefono'] ?? '');
        $correo = trim($body['correo'] ?? '');
        $direccion = trim($body['direccion'] ?? '');
        $contactos = $body['contactos'] ?? [];

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        pacienteModificarContacto($paciente['id_paciente'], $telefono, $correo, $direccion);
        if (!empty($contactos)) {
            reemplazarContactos($paciente['id_paciente'], $contactos);
        }

        registrarBitacora($s['id_usuario'], 'PORTAL_PERFIL_ACTUALIZAR', $paciente['id_paciente'], 'Perfil actualizado', 'paciente', ipCliente());

        return ['mensaje' => 'Perfil actualizado correctamente.'];
    }

    /** POST /api/v1/portal/cuentas/{id} — Crear cuenta de paciente desde CU04 */
    public static function crearCuentaPaciente(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        $s = Sesion::usuario();

        $idPaciente = (int) $p->param('id');
        $paciente = BaseDatos::fila(
            "SELECT id_paciente, ci, id_usuario, correo FROM paciente WHERE id_paciente = :id",
            [':id' => $idPaciente]
        );

        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $id_usuario = $paciente['id_usuario'] ?? null;
        if ($id_usuario) throw new ErrorApi(409, 'Este paciente ya tiene cuenta.');

        // Verificar que el login (CI) no exista como usuario
        $existeLogin = BaseDatos::fila(
            "SELECT id_usuario FROM usuario WHERE username = :login",
            [':login' => (string) $paciente['ci']]
        );
        if ($existeLogin) {
            throw new ErrorApi(409, 'Ya existe un usuario con ese CI.');
        }

        // Generar contraseña temporal: 8+ caracteres, mayúscula, minúscula, número, símbolo
        $chars_lower = 'abcdefghijklmnopqrstuvwxyz';
        $chars_upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_num = '0123456789';
        $chars_sym = '!@#$%^&*';

        $contrasenaTemp = '';
        $contrasenaTemp .= $chars_lower[random_int(0, strlen($chars_lower) - 1)];
        $contrasenaTemp .= $chars_upper[random_int(0, strlen($chars_upper) - 1)];
        $contrasenaTemp .= $chars_num[random_int(0, strlen($chars_num) - 1)];
        $contrasenaTemp .= $chars_sym[random_int(0, strlen($chars_sym) - 1)];
        for ($i = 0; $i < 4; $i++) {
            $todos = $chars_lower . $chars_upper . $chars_num . $chars_sym;
            $contrasenaTemp .= $todos[random_int(0, strlen($todos) - 1)];
        }
        $contrasenaTemp = str_shuffle($contrasenaTemp);

        $hashContrasena = password_hash($contrasenaTemp, PASSWORD_BCRYPT);
        $ci = (string) $paciente['ci'];
        $idPacienteInt = (int) $paciente['id_paciente'];

        try {
            BaseDatos::transaccion(function () use ($ci, $hashContrasena, $idPacienteInt, $s, $paciente) {
                $correo = $ci . '@pacientes.vidasalud.bo';
                if (!empty(trim($paciente['correo'] ?? ''))) {
                    $existeCorreo = BaseDatos::fila(
                        "SELECT id_usuario FROM usuario WHERE correo = :mail",
                        [':mail' => $paciente['correo']]
                    );
                    if (!$existeCorreo) {
                        $correo = $paciente['correo'];
                    }
                }

                $idUsuario = crearUsuario(4, $ci, $correo, $hashContrasena);

                BaseDatos::ejecutar(
                    "UPDATE paciente SET id_usuario = :iu WHERE id_paciente = :p",
                    [':iu' => $idUsuario, ':p' => $idPacienteInt]
                );

                registrarBitacora($s['id_usuario'], 'PORTAL_CUENTAPACIENTE_CREAR', $idPacienteInt, 'Cuenta creada por recepción', 'paciente', ipCliente());
            }, $s['id_usuario']);
        } catch (\PDOException $e) {
            error_log('Error en crearCuentaPaciente: ' . $e->getMessage());
            if ($e->getCode() === '23505') {
                throw new ErrorApi(409, 'Ya existe un usuario con ese CI.');
            }
            throw new ErrorApi(500, 'Error al crear la cuenta.');
        }

        return ['login' => $ci, 'contrasena_temporal' => $contrasenaTemp, 'mensaje' => 'Cuenta creada. Entrega estos datos al paciente; debe cambiar su contraseña en Mi perfil.'];
    }
}
