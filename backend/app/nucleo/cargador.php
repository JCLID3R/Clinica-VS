<?php
/**
 * Carga los archivos del núcleo y de los 6 paquetes (sin Composer).
 * Orden: núcleo -> por cada paquete: schemas, models, repository, politicas, casos_uso, router.
 */
declare(strict_types=1);

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Respuesta.php';
require_once __DIR__ . '/Errores.php';
require_once __DIR__ . '/BaseDatos.php';
require_once __DIR__ . '/Enrutador.php';
require_once __DIR__ . '/Sesion.php';
require_once __DIR__ . '/Validador.php';
require_once __DIR__ . '/Cors.php';

const PAQUETES = ['seguridad', 'personas', 'agenda', 'citas', 'reportes', 'portal_paciente'];

foreach (PAQUETES as $paquete) {
    $dir = __DIR__ . "/../paquetes/{$paquete}";
    foreach (['schemas.php', 'models.php', 'repository.php', 'politicas.php'] as $archivo) {
        require_once "{$dir}/{$archivo}";
    }
    foreach (glob("{$dir}/casos_uso/cu*.php") as $casoUso) {
        require_once $casoUso;
    }
}

// ---- CU01: inicio ----
// Envío de correos (recuperación y aviso de cambio de contraseña)
require_once __DIR__ . '/Correo.php';
// ---- CU01: fin ----
