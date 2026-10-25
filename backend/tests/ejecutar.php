<?php
/**
 * Pruebas mínimas de la API (sin dependencias).
 * Uso (desde backend/, con la API levantada en el puerto 8000):
 *     php tests/ejecutar.php
 * Cada paquete agregará sus pruebas en tests/<paquete>/test_*.php
 */
declare(strict_types=1);

const API = 'http://localhost:8000/api/v1';
$fallos = 0;

function peticion(string $metodo, string $ruta, ?array $cuerpo = null): array
{
    $ctx = stream_context_create(['http' => [
        'method' => $metodo, 'ignore_errors' => true,
        'header' => "Content-Type: application/json\r\n",
        'content' => $cuerpo ? json_encode($cuerpo) : '',
    ]]);
    $texto = file_get_contents(API . $ruta, false, $ctx);
    preg_match('#HTTP/\S+ (\d{3})#', $http_response_header[0] ?? '', $m);
    return [(int) ($m[1] ?? 0), json_decode($texto ?: 'null', true)];
}

function prueba(string $nombre, bool $ok): void
{
    global $fallos;
    echo ($ok ? "  OK   " : "  FALLA ") . $nombre . PHP_EOL;
    if (!$ok) $fallos++;
}

echo "Pruebas de la API\n";
[$e, $r] = peticion('GET', '/salud');
prueba('GET /salud responde 200', $e === 200 && ($r['estado'] ?? '') === 'ok');
[$e, $r] = peticion('GET', '/salud/bd');
prueba('GET /salud/bd conecta con PostgreSQL', $e === 200);
[$e, $r] = peticion('GET', '/rutas');
prueba('GET /rutas lista los endpoints de los 17 casos de uso', $e === 200 && count($r) >= 40);
[$e, $r] = peticion('GET', '/no-existe');
prueba('Ruta inexistente responde 404', $e === 404);

foreach (glob(__DIR__ . '/*/test_*.php') as $archivo) {
    require $archivo;
}
echo $fallos === 0 ? "\nTodas las pruebas pasaron.\n" : "\n{$fallos} prueba(s) fallaron.\n";
exit($fallos === 0 ? 0 : 1);
