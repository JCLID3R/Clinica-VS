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
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu16GestionarPerfilPaciente
{
    /** POST /api/v1/portal/registro — Crear cuenta del portal */
    public static function registrarCuenta(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        throw new NoImplementado('CU16');
    }
    /** GET /api/v1/portal/perfil — Mi perfil */
    public static function obtenerPerfil(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        throw new NoImplementado('CU16');
    }
    /** PUT /api/v1/portal/perfil — Actualizar mi perfil */
    public static function actualizarPerfil(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        throw new NoImplementado('CU16');
    }
}
