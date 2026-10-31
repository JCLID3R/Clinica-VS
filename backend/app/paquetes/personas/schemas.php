<?php
/**
 * Paquete personas — validación de los datos que entran y salen de la API.
 * Cada función recibe el cuerpo JSON de la petición y devuelve los datos limpios,
 * o lanza ErrorApi(400) con los errores por campo (usar App\Nucleo\Validador).
 *
 * Estado: PENDIENTE. Agregar aquí una función por formulario, p. ej.:
 *     function validar_<entidad>(array $datos): array { ... }
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\Schemas;

use App\Nucleo\{ErrorApi, Validador};

// ---- CU06: inicio ----
function validar_listar_especialidades(array $params): array
{
    return [
        'filtro' => (string) ($params['filtro'] ?? ''),
        'estado' => (string) ($params['estado'] ?? ''),
        'pagina' => max(1, (int) ($params['pagina'] ?? 1)),
        'por_pagina' => min(100, max(1, (int) ($params['por_pagina'] ?? 20))),
    ];
}

function validar_registrar_especialidad(array $datos): array
{
    $errores = [];
    $v = new Validador($datos);

    $nombre = trim($datos['nombre'] ?? '');
    if (!$nombre) {
        $errores['nombre'] = 'El nombre es requerido.';
    } elseif (strlen($nombre) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (strlen($nombre) > 60) {
        $errores['nombre'] = 'El nombre no debe exceder 60 caracteres.';
    }

    $descripcion = trim($datos['descripcion'] ?? '');
    if ($descripcion && strlen($descripcion) > 200) {
        $errores['descripcion'] = 'La descripción no debe exceder 200 caracteres.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida.', $errores);
    }

    return [
        'nombre' => $nombre,
        'descripcion' => $descripcion ?: null,
    ];
}

function validar_modificar_especialidad(array $datos): array
{
    $errores = [];

    $nombre = trim($datos['nombre'] ?? '');
    if (!$nombre) {
        $errores['nombre'] = 'El nombre es requerido.';
    } elseif (strlen($nombre) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (strlen($nombre) > 60) {
        $errores['nombre'] = 'El nombre no debe exceder 60 caracteres.';
    }

    $descripcion = trim($datos['descripcion'] ?? '');
    if ($descripcion && strlen($descripcion) > 200) {
        $errores['descripcion'] = 'La descripción no debe exceder 200 caracteres.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida.', $errores);
    }

    return [
        'nombre' => $nombre,
        'descripcion' => $descripcion ?: null,
    ];
}

function validar_cambiar_estado_especialidad(array $datos): array
{
    $estado = strtolower(trim($datos['estado'] ?? ''));
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        throw new ErrorApi(400, 'Estado inválido. Debe ser "activo" o "inactivo".', ['estado' => $estado]);
    }

    return ['estado' => $estado];
}
// ---- CU06: fin ----
