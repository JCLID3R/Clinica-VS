<?php
/**
 * Cors: permite que el frontend llame a la API cuando corre en otro origen
 * (por ejemplo frontend en http://localhost:5500 y backend en http://localhost:8000).
 * Si el frontend y el backend se sirven desde el mismo Apache (XAMPP), CORS no es necesario.
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Cors
{
    public static function aplicar(): void
    {
        $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origen !== '' && in_array($origen, Config::origenesCors(), true)) {
            header("Access-Control-Allow-Origin: {$origen}");
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
