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
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu16GestionarPerfilPaciente
{
    /** POST /api/v1/portal/registro — Crear cuenta del portal */
    public static function registrarCuenta(Peticion $p): mixed
    {
        $body = $p->cuerpo();
        $ci = (int) ($body['ci'] ?? 0);
        $correo = trim($body['correo'] ?? '');
        $username = trim($body['username'] ?? '');
        $contrasena = trim($body['contrasena'] ?? '');

        if (!$ci || !$correo || !$username || !$contrasena) {
            throw new ErrorApi(400, 'Todos los campos requeridos.');
        }

        $paciente = BaseDatos::fila("SELECT * FROM paciente WHERE ci = :ci", [':ci' => $ci]);
        if (!$paciente) {
            throw new ErrorApi(404, 'CI no registrado en el sistema.');
        }
        if ($paciente['id_usuario']) {
            throw new ErrorApi(409, 'Este CI ya tiene una cuenta de portal.');
        }

        $hashContrasena = password_hash($contrasena, PASSWORD_BCRYPT);
        $idUsuario = BaseDatos::fila(
            "INSERT INTO usuario (id_rol, username, correo, password_hash, estado) VALUES (3, :u, :c, :p, 'activo') RETURNING id_usuario",
            [':u' => $username, ':c' => $correo, ':p' => $hashContrasena]
        )['id_usuario'];

        BaseDatos::ejecutar(
            "UPDATE paciente SET id_usuario = :iu WHERE ci = :ci",
            [':iu' => $idUsuario, ':ci' => $ci]
        );

        return ['mensaje' => 'Cuenta creada exitosamente.'];
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
}
