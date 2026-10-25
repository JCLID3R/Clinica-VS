<?php
/**
 * Paquete portal_paciente — acceso a datos (consultas SQL parametrizadas con PDO).
 * Solo este archivo escribe SQL del paquete. Usa App\Nucleo\BaseDatos::consultar(), fila(), ejecutar() y transaccion().
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\Repository;

use App\Nucleo\BaseDatos;
