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
 * Estado: PENDIENTE.
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
        throw new NoImplementado('CU02');
    }
    /** POST /api/v1/seguridad/usuarios — Registrar usuario */
    public static function crear(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        throw new NoImplementado('CU02');
    }
    /** PUT /api/v1/seguridad/usuarios/{id} — Modificar usuario */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        throw new NoImplementado('CU02');
    }
    /** PATCH /api/v1/seguridad/usuarios/{id}/estado — Activar o desactivar usuario */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        throw new NoImplementado('CU02');
    }
}
