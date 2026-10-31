<?php
/**
 * cu06_gestionar_especialidades.php
 * CU06 - Gestionar especialidades
 * Paquete: personas  ·  Ciclo 1
 *
 * Administrar especialidades médicas: nombre (obligatorio, 3-60 caracteres, primera letra mayúscula),
 * descripción (opcional, hasta 200), desactivar con validación de médicos activos.
 *
 * Actores ........: Administrador
 * Permiso ........: especialidad.gestionar
 * Tablas .........: especialidad, medico_especialidad, bitacora
 *
 * Excepciones:
 *     - Nombre vacío o < 3 caracteres
 *     - Nombre > 60 caracteres
 *     - Espacios extremos (se trimean)
 *     - Primera letra no mayúscula (se corrige)
 *     - Nombre duplicado (índice ux_especialidad_nombre: lower(trim(nombre)))
 *     - Desactivar con médicos activos: requiere confirmación
 *
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\CasosUso;

use App\Nucleo\{Peticion, Sesion};

final class Cu06GestionarEspecialidades
{
    /** GET /api/v1/personas/especialidades — Listar especialidades */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        return Cu06Especialidades::listar($p);
    }

    /** POST /api/v1/personas/especialidades — Registrar especialidad */
    /** POST /api/v1/personas/especialidades — Registrar especialidad */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        return Cu06Especialidades::registrar($p);
    }

    /** PUT /api/v1/personas/especialidades/{id} — Modificar especialidad */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        return Cu06Especialidades::modificar($p);
    }

    /** PATCH /api/v1/personas/especialidades/{id}/estado — Cambiar estado */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        return Cu06Especialidades::cambiarEstado($p);
    }
}

// ---- CU06: inicio ----
use App\Nucleo\{BaseDatos, ErrorApi};
use function App\Paquetes\Personas\Schemas\{
    validar_listar_especialidades, validar_registrar_especialidad,
    validar_modificar_especialidad, validar_cambiar_estado_especialidad
};
use function App\Paquetes\Personas\Politicas\{normalizarNombreEspecialidad, ipCliente};
use function App\Paquetes\Personas\Repository\{
    listarEspecialidades, existeEspecialidadPorNombre, registrarEspecialidad,
    modificarEspecialidad, cambiarEstadoEspecialidad, contarMedicosPorEspecialidad,
    registrarBitacora, obtenerEspecialidadPorId
};

final class Cu06Especialidades
{
    public static function listar(Peticion $p): mixed
    {
        $filtro = $p->consulta('filtro') ?? '';
        $estado = $p->consulta('estado') ?? '';
        $pagina = max(1, (int) ($p->consulta('pagina') ?? 1));
        $por_pagina = min(100, max(1, (int) ($p->consulta('por_pagina') ?? 20)));

        return listarEspecialidades($filtro, $estado, $pagina, $por_pagina);
    }

    public static function registrar(Peticion $p): mixed
    {
        $datos = validar_registrar_especialidad($p->cuerpo());

        // Normalizar nombre
        $nombre = normalizarNombreEspecialidad($datos['nombre']);

        // Verificar duplicado
        if (existeEspecialidadPorNombre($nombre)) {
            throw new ErrorApi(400, 'La especialidad ya existe.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($nombre, $datos, $s, $ip) {
            $id = registrarEspecialidad($nombre, $datos['descripcion'] ?? null);
            registrarBitacora($s['id_usuario'], 'ESPECIALIDAD_CREAR', $id, "Especialidad registrada: {$nombre}", 'especialidad', $ip);
        });

        return ['mensaje' => 'Especialidad registrada correctamente.'];
    }

    public static function modificar(Peticion $p): mixed
    {
        $id = (int) ($p->ruta['id'] ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $datos = validar_modificar_especialidad($p->cuerpo());
        $nombre = normalizarNombreEspecialidad($datos['nombre']);

        // Obtener especialidad actual
        $existente = obtenerEspecialidadPorId($id);
        if (!$existente) throw new ErrorApi(404, 'Especialidad no encontrada.');

        // Verificar que no exista otro con ese nombre
        if (strtolower(trim($existente['nombre'])) !== strtolower(trim($nombre))) {
            if (existeEspecialidadPorNombre($nombre)) {
                throw new ErrorApi(400, 'La especialidad ya existe.');
            }
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $nombre, $datos, $s, $ip, $existente) {
            modificarEspecialidad($id, $nombre, $datos['descripcion'] ?? null);
            $detalle = "Especialidad modificada: {$existente['nombre']} → {$nombre}";
            registrarBitacora($s['id_usuario'], 'ESPECIALIDAD_MODIFICAR', $id, $detalle, 'especialidad', $ip);
        });

        return ['mensaje' => 'Especialidad actualizada correctamente.'];
    }

    public static function cambiarEstado(Peticion $p): mixed
    {
        $id = (int) ($p->ruta['id'] ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $datos = validar_cambiar_estado_especialidad($p->cuerpo());
        $nuevo_estado = $datos['estado'];

        $especialidad = obtenerEspecialidadPorId($id);
        if (!$especialidad) throw new ErrorApi(404, 'Especialidad no encontrada.');

        // Si desactiva, verificar si hay médicos activos
        if ($nuevo_estado === 'inactivo' && $especialidad['estado'] === 'activo') {
            $cantidad = contarMedicosPorEspecialidad($id, 'activo');
            if ($cantidad > 0) {
                throw new ErrorApi(409, "No se puede desactivar. Hay {$cantidad} médicos activos con esta especialidad.",
                    ['medicos_activos' => $cantidad]);
            }
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $nuevo_estado, $s, $ip, $especialidad) {
            cambiarEstadoEspecialidad($id, $nuevo_estado);
            $detalle = "Estado: {$especialidad['estado']} → {$nuevo_estado}";
            registrarBitacora($s['id_usuario'], 'ESPECIALIDAD_ESTADO', $id, $detalle, 'especialidad', $ip);
        });

        return ['mensaje' => 'Estado actualizado correctamente.'];
    }
}
// ---- CU06: fin ----
