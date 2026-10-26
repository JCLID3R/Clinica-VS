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
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\CasosUso;

use App\Nucleo\{Peticion, Sesion};

final class Cu03GestionarRolesPermisos
{
    /** GET /api/v1/seguridad/roles — Listar roles */
    public static function listarRoles(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        return Cu03Roles::listarRoles();
    }
    /** GET /api/v1/seguridad/permisos — Listar permisos */
    public static function listarPermisos(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        return Cu03Roles::listarPermisos();
    }
    /** GET /api/v1/seguridad/roles/{id}/permisos — Permisos de un rol */
    public static function permisosDelRol(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        return Cu03Roles::permisosDelRol($p);
    }
    /** PUT /api/v1/seguridad/roles/{id}/permisos — Asignar permisos a un rol */
    public static function asignarPermisos(Peticion $p): mixed
    {
        Sesion::requerir('rol.gestionar');
        return Cu03Roles::asignarPermisos($p);
    }
}

// ---- CU03: inicio ----
use App\Nucleo\{BaseDatos, ErrorApi};
use function App\Paquetes\Seguridad\Schemas\validar_asignar_permisos;
use function App\Paquetes\Seguridad\Politicas\ipCliente;
use function App\Paquetes\Seguridad\Repository\{
    listarRoles, listarPermisos, obtenerPermisosRol, buscarRolPorId,
    reemplazarPermisosRol, obtenerCodigosPermisosRol, registrarBitacora
};

final class Cu03Roles
{
    public static function listarRoles(): array
    {
        $roles = listarRoles();
        return [
            'roles' => array_map(static fn ($r) => [
                'id_rol' => (int) $r['id_rol'],
                'nombre' => $r['nombre'],
                'descripcion' => $r['descripcion'],
                'estado' => $r['estado'],
            ], $roles),
        ];
    }

    public static function listarPermisos(): array
    {
        $permisos = listarPermisos();
        return [
            'permisos' => array_map(static fn ($p) => [
                'id_permiso' => (int) $p['id_permiso'],
                'codigo' => $p['codigo'],
                'descripcion' => $p['descripcion'],
            ], $permisos),
        ];
    }

    public static function permisosDelRol(Peticion $p): array
    {
        $idRol = (int) $p->param('id');
        $rol = buscarRolPorId($idRol);
        if (!$rol) {
            throw new ErrorApi(404, 'Rol no encontrado.');
        }

        $permisos = obtenerPermisosRol($idRol);
        return [
            'id_rol' => $idRol,
            'rol' => $rol['nombre'],
            'estado' => $rol['estado'],
            'permisos' => array_map(static fn ($p) => [
                'id_permiso' => (int) $p['id_permiso'],
                'codigo' => $p['codigo'],
            ], $permisos),
        ];
    }

    public static function asignarPermisos(Peticion $p): array
    {
        $s = Sesion::usuario();
        $idRol = (int) $p->param('id');
        $d = validar_asignar_permisos($p->cuerpo());
        $ip = ipCliente();

        return BaseDatos::transaccion(static function () use ($s, $idRol, $d, $ip): array {
            $rol = buscarRolPorId($idRol);
            if (!$rol) {
                throw new ErrorApi(404, 'Rol no encontrado.');
            }

            if ($rol['estado'] !== 'activo') {
                throw new ErrorApi(400, 'No se pueden asignar permisos a un rol inactivo.');
            }

            $idsPermisosNuevos = $d['permisos'];
            $codigosActuales = obtenerCodigosPermisosRol($idRol);

            if ($rol['nombre'] === 'Administrador') {
                if (in_array('rol.gestionar', $codigosActuales) && !codigoEnPermisos($idsPermisosNuevos, 'rol.gestionar')) {
                    throw new ErrorApi(400, 'No se puede quitar el permiso rol.gestionar al Administrador.');
                }
                if (in_array('usuario.gestionar', $codigosActuales) && !codigoEnPermisos($idsPermisosNuevos, 'usuario.gestionar')) {
                    throw new ErrorApi(400, 'No se puede quitar el permiso usuario.gestionar al Administrador.');
                }
            }

            reemplazarPermisosRol($idRol, $idsPermisosNuevos);

            $codigosNuevos = obtenerCodigosPermisosRol($idRol);
            $agregados = array_diff($codigosNuevos, $codigosActuales);
            $quitados = array_diff($codigosActuales, $codigosNuevos);

            $detalle = 'Agregados: ' . (count($agregados) ? implode(', ', $agregados) : 'ninguno');
            $detalle .= '; Quitados: ' . (count($quitados) ? implode(', ', $quitados) : 'ninguno');

            registrarBitacora($s['id_usuario'], 'ROL_PERMISOS', $idRol, $detalle, $ip);

            return [
                'mensaje' => 'Permisos del rol actualizados. Los cambios se aplican en el siguiente inicio de sesión.',
                'id_rol' => $idRol,
                'rol' => $rol['nombre'],
            ];
        }, $s['id_usuario']);
    }
}

/** Verifica si un código de permiso está en la lista de IDs de permisos. */
function codigoEnPermisos(array $idsPermisos, string $codigo): bool
{
    $idPermiso = BaseDatos::fila('SELECT id_permiso FROM permiso WHERE codigo = :c', [':c' => $codigo]);
    if (!$idPermiso) {
        return false;
    }
    return in_array((int) $idPermiso['id_permiso'], array_map('intval', $idsPermisos), true);
}
// ---- CU03: fin ----
