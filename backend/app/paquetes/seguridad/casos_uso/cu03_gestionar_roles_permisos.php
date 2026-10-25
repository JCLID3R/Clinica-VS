<?php
/**
 * CU03 - Gestionar roles y permisos
 * Paquete: seguridad  ·  Ciclo 1  ·  Prioridad Media  ·  Requisito: RF01 / RN12
 *
 * Definir qué operaciones puede realizar cada rol (Administrador, Recepcionista, Médico y
 * Paciente), por ejemplo autorizar al médico a marcar citas como atendidas.
 *
 * Actores ........: Administrador
 * Permiso ........: rol.gestionar
 * Tablas .........: rol, permiso, rol_permiso
 *
 * Precondición:
 *     El administrador inició sesión (CU01).
 *
 * Flujo principal:
 *     1. El administrador ingresa a la opción «Roles y permisos».
 *     2. El sistema muestra los roles y, para el rol seleccionado, la lista de permisos con casillas de verificación.
 *     3. El administrador marca o desmarca los permisos del rol.
 *     4. El sistema guarda la asignación en una transacción y registra el cambio en la bitácora.
 *     5. Los nuevos permisos se aplican en el siguiente inicio de sesión de los usuarios del rol.
 *
 * Excepciones / reglas que debe controlar:
 *     - Intento de quitar al rol Administrador el permiso de gestionar roles (se impide quedar sin administrador).
 *     - Rol inactivo: no se le pueden asignar permisos.
 *
 * Postcondición:
 *     El rol queda con el conjunto de permisos actualizado.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu03GestionarRolesPermisos
{
    /** GET /api/v1/seguridad/roles — Listar roles */
    public static function listarRoles(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        throw new NoImplementado('CU03');
    }
    /** GET /api/v1/seguridad/permisos — Listar permisos */
    public static function listarPermisos(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        throw new NoImplementado('CU03');
    }
    /** GET /api/v1/seguridad/roles/{id}/permisos — Permisos de un rol */
    public static function permisosDelRol(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        throw new NoImplementado('CU03');
    }
    /** PUT /api/v1/seguridad/roles/{id}/permisos — Asignar permisos a un rol */
    public static function asignarPermisos(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        throw new NoImplementado('CU03');
    }
}
