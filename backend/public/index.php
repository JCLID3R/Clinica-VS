<?php
/**
 * Punto de entrada de la API (controlador frontal).
 *
 * Levantar en local (desde backend/):   php -S localhost:8000 -t public public/index.php
 * Con XAMPP:                            http://localhost/clinica-vida-salud/backend/public/api/v1/salud
 *
 * Todas las rutas empiezan con /api/v1. GET /api/v1/rutas lista los endpoints registrados.
 */
declare(strict_types=1);

use App\Nucleo\{Cors, Enrutador, Errores, BaseDatos, Config};

require_once __DIR__ . '/../app/nucleo/cargador.php';

set_exception_handler([Errores::class, 'manejar']);
set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

Cors::aplicar();

$enrutador = new Enrutador('/api/v1');

// ---- Salud de la API ---------------------------------------------------------
$enrutador->get('/salud', static fn () => ['estado' => 'ok', 'version' => '0.1.0'], 'Comprueba que la API está levantada');
$enrutador->get('/salud/bd', static function () {
    $fila = BaseDatos::fila("SELECT current_database() AS base, current_setting('server_version') AS version");
    return ['estado' => 'ok', 'base' => $fila['base'], 'postgres' => 'PostgreSQL ' . $fila['version']];
}, 'Comprueba la conexión con PostgreSQL');
$enrutador->get('/rutas', static fn () => $enrutador->listar(), 'Lista de endpoints registrados');

// ---- Rutas de los 6 paquetes ---------------------------------------------------
foreach (PAQUETES as $paquete) {
    (require __DIR__ . "/../app/paquetes/{$paquete}/router.php")($enrutador);
}

$enrutador->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
