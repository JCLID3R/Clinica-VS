<?php
/**
 * Paquete citas — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - El médico y el paciente deben estar activos (RN04).
 *   - La disponibilidad se verifica en el servidor dentro de una transacción (RN13); la BD impide superposiciones (RN07).
 *   - Transiciones válidas: Programada → Confirmada/Atendida/Cancelada/Inasistencia; Confirmada → Atendida/Cancelada/Inasistencia.
 *   - Las citas no se eliminan; las canceladas liberan el horario (RN09, RN10).
 *   - Un médico solo consulta su propia agenda.
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\Politicas;

use App\Nucleo\ErrorApi;
