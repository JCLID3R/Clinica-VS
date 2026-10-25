<?php
/**
 * Paquete seguridad — reglas de negocio y permisos comunes a los casos de uso del paquete.
 *
 * Reglas que se implementarán aquí:
 *   - Solo el Administrador gestiona usuarios, roles y permisos (permisos usuario.gestionar y rol.gestionar).
 *   - Una cuenta 'inactiva' o 'bloqueada' no puede iniciar sesión; tras 5 intentos fallidos se bloquea.
 *   - El rol Administrador no puede quedarse sin el permiso rol.gestionar.
 *   - Las contraseñas se guardan con password_hash() y se verifican con password_verify().
 *
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\Politicas;

use App\Nucleo\ErrorApi;
