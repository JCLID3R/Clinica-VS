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
 * Estado: PENDIENTE.
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
        throw new NoImplementado('CU01');
    }
    /** DELETE /api/v1/seguridad/sesion — Cerrar sesión */
    public static function cerrar(Peticion $p): mixed
    {
        // CU01 no exige sesión previa
        throw new NoImplementado('CU01');
    }
    /** GET /api/v1/seguridad/sesion — Usuario de la sesión actual */
    public static function actual(Peticion $p): mixed
    {
        // CU01 no exige sesión previa
        throw new NoImplementado('CU01');
    }
}
