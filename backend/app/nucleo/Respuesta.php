<?php
/**
 * Respuesta: todas las respuestas de la API son JSON.
 *   Éxito: los datos directamente.  Error: {"error": {"estado": 404, "mensaje": "...", "detalle": {...}}}
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Respuesta
{
    public static function json(mixed $datos, int $estado = 200): never
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function creado(mixed $datos): never
    {
        self::json($datos, 201);
    }

    public static function sinContenido(): never
    {
        http_response_code(204);
        exit;
    }

    public static function error(int $estado, string $mensaje, array $detalle = []): never
    {
        self::json(['error' => array_filter(['estado' => $estado, 'mensaje' => $mensaje, 'detalle' => $detalle ?: null])], $estado);
    }
}
