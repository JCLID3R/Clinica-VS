<?php
/**
 * Paquete personas — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - No se registran dos pacientes con el mismo CI (RN01) ni dos médicos con el mismo registro profesional (RN02).
 *   - No se eliminan pacientes ni médicos con historial: se desactivan (RN11).
 *   - Un médico debe tener al menos una especialidad activa.
 *   - No se permiten especialidades con el mismo nombre normalizado (RF04).
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\Politicas;

use App\Nucleo\ErrorApi;

// ---- CU06: inicio ----
function normalizarNombreEspecialidad(string $nombre): string
{
    $nombre = trim($nombre);
    // Convertir la primera letra a mayúscula y el resto a minúsculas
    return ucfirst(strtolower($nombre));
}

function ipCliente(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
// ---- CU06: fin ----
