<?php
/**
 * Paquete reportes — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - Solo usuarios con el permiso reporte.generar.
 *   - La fecha inicial no puede ser mayor que la final.
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Reportes\Politicas;

use App\Nucleo\ErrorApi;
