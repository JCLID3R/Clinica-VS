<?php
/**
 * Enrutador: asocia "MÉTODO /ruta/{parametro}" con la función del caso de uso.
 * Cada paquete registra sus rutas en su router.php.
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Enrutador
{
    /** @var array<int, array{metodo:string, patron:string, ruta:string, accion:callable, resumen:string}> */
    private array $rutas = [];

    public function __construct(private readonly string $prefijo = '/api/v1')
    {
    }

    public function get(string $ruta, callable $accion, string $resumen = ''): void    { $this->agregar('GET', $ruta, $accion, $resumen); }
    public function post(string $ruta, callable $accion, string $resumen = ''): void   { $this->agregar('POST', $ruta, $accion, $resumen); }
    public function put(string $ruta, callable $accion, string $resumen = ''): void    { $this->agregar('PUT', $ruta, $accion, $resumen); }
    public function patch(string $ruta, callable $accion, string $resumen = ''): void  { $this->agregar('PATCH', $ruta, $accion, $resumen); }
    public function delete(string $ruta, callable $accion, string $resumen = ''): void { $this->agregar('DELETE', $ruta, $accion, $resumen); }

    private function agregar(string $metodo, string $ruta, callable $accion, string $resumen): void
    {
        $ruta = $this->prefijo . $ruta;
        $patron = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $ruta) . '$#';
        $this->rutas[] = compact('metodo', 'patron', 'ruta', 'accion', 'resumen');
    }

    /** Lista de rutas registradas (la muestra GET /api/v1/rutas). */
    public function listar(): array
    {
        return array_map(fn ($r) => ['metodo' => $r['metodo'], 'ruta' => $r['ruta'], 'resumen' => $r['resumen']], $this->rutas);
    }

    public function despachar(string $metodo, string $uri): never
    {
        $camino = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';
        // Si la API vive en una subcarpeta (p. ej. /clinica/backend/public), se quita lo que está antes de /api/
        $pos = strpos($camino, $this->prefijo);
        if ($pos !== false) {
            $camino = substr($camino, $pos);
        }
        $metodoPermitido = false;
        foreach ($this->rutas as $r) {
            if (!preg_match($r['patron'], $camino, $m)) {
                continue;
            }
            if ($r['metodo'] !== $metodo) {
                $metodoPermitido = true;
                continue;
            }
            $parametros = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $resultado = ($r['accion'])(new Peticion($parametros));
            if ($resultado === null) {
                Respuesta::sinContenido();
            }
            Respuesta::json($resultado, $metodo === 'POST' ? 201 : 200);
        }
        throw $metodoPermitido ? new ErrorApi(405, 'Método no permitido.') : new ErrorApi(404, 'Ruta no encontrada.');
    }
}

/** Datos de la petición: parámetros de la ruta, de la consulta (?a=b) y cuerpo JSON. */
final class Peticion
{
    private ?array $cuerpo = null;

    public function __construct(public readonly array $ruta)
    {
    }

    public function param(string $nombre): ?string
    {
        return $this->ruta[$nombre] ?? null;
    }

    public function consulta(string $nombre, ?string $porDefecto = null): ?string
    {
        $v = $_GET[$nombre] ?? $porDefecto;
        return is_string($v) ? trim($v) : $porDefecto;
    }

    public function cuerpo(): array
    {
        if ($this->cuerpo === null) {
            $texto = file_get_contents('php://input') ?: '';
            $datos = $texto === '' ? [] : json_decode($texto, true);
            if (!is_array($datos)) {
                throw new ErrorApi(400, 'El cuerpo de la petición debe ser JSON.');
            }
            $this->cuerpo = $datos;
        }
        return $this->cuerpo;
    }
}
