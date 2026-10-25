<?php
/**
 * Paquete portal_paciente — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - El paciente solo ve y modifica su propia información y sus citas.
 *   - Al reservar se aplican las mismas reglas que CU09 (Programar cita).
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\Politicas;

use App\Nucleo\ErrorApi;
