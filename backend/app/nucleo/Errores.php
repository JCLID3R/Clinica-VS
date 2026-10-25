<?php
/**
 * Errores de la API y su conversión a respuestas JSON.
 *
 * - ErrorApi:     error controlado (400 datos inválidos, 401 sin sesión, 403 sin permiso, 404, 409 conflicto…).
 * - NoImplementado: lo lanzan los casos de uso que todavía están PENDIENTES (responde 501).
 * - Los errores de PostgreSQL más comunes se traducen a mensajes claros, sin mostrar detalles internos.
 */
declare(strict_types=1);

namespace App\Nucleo;

use PDOException;
use Throwable;

class ErrorApi extends \RuntimeException
{
    public function __construct(public readonly int $estado, string $mensaje, public readonly array $detalle = [])
    {
        parent::__construct($mensaje);
    }
}

final class NoImplementado extends ErrorApi
{
    public function __construct(string $casoUso)
    {
        parent::__construct(501, "{$casoUso}: caso de uso pendiente de implementar.");
    }
}

final class Errores
{
    /** Convierte cualquier excepción en una respuesta JSON. */
    public static function manejar(Throwable $e): never
    {
        if ($e instanceof ErrorApi) {
            Respuesta::error($e->estado, $e->getMessage(), $e->detalle);
        }
        if ($e instanceof PDOException) {
            [$estado, $mensaje] = self::traducirPostgres($e);
            error_log('PDO: ' . $e->getMessage());
            Respuesta::error($estado, $mensaje);
        }
        error_log('Error no controlado: ' . $e);
        Respuesta::error(500, Config::esDesarrollo() ? $e->getMessage() : 'Ocurrió un error inesperado.');
    }

    /** SQLSTATE de PostgreSQL → [código HTTP, mensaje para el usuario]. */
    private static function traducirPostgres(PDOException $e): array
    {
        return match ((string) $e->getCode()) {
            '23505' => [409, 'Ya existe un registro con esos datos (valor duplicado).'],
            '23P01' => [409, 'El horario ya está ocupado.'],
            '23503' => [409, 'El registro está relacionado con otros datos.'],
            '23514' => [400, 'Los datos no cumplen las reglas de la clínica.'],
            'P0001' => [409, self::mensajeTrigger($e->getMessage())],
            default => [500, 'Error en la base de datos.'],
        };
    }

    /** Extrae el mensaje que lanza un trigger con RAISE EXCEPTION. */
    private static function mensajeTrigger(string $texto): string
    {
        return preg_match('/ERROR:\s+(.+?)(\n|CONTEXT|$)/s', $texto, $m) ? trim($m[1]) : 'Operación no permitida.';
    }
}
