<?php
/**
 * Paquete agenda — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - La hora final debe ser posterior a la inicial y los bloques de un médico no se superponen.
 *   - Duraciones permitidas: 15, 20, 30, 45 o 60 minutos (RN08).
 *   - Solo se ofrecen horarios futuros, dentro del horario de atención y no bloqueados (RN05, RN06).
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\Politicas;

use App\Nucleo\ErrorApi;
