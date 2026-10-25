<?php
/**
 * Config: lee la configuración desde backend/.env (ver .env.example).
 * Las credenciales nunca se escriben en el código ni se suben al repositorio.
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Config
{
    private static ?array $valores = null;

    /** Devuelve el valor de una variable del .env (o de las variables de entorno del sistema). */
    public static function obtener(string $clave, ?string $porDefecto = null): ?string
    {
        if (self::$valores === null) {
            self::$valores = self::leerEnv(__DIR__ . '/../../.env');
        }
        $valor = getenv($clave);
        if ($valor !== false && $valor !== '') {
            return $valor;
        }
        return self::$valores[$clave] ?? $porDefecto;
    }

    /** Orígenes permitidos para CORS (frontend), separados por coma en FRONTEND_ORIGENES. */
    public static function origenesCors(): array
    {
        $lista = self::obtener('FRONTEND_ORIGENES', '');
        return array_values(array_filter(array_map('trim', explode(',', $lista))));
    }

    public static function esDesarrollo(): bool
    {
        return self::obtener('APP_ENTORNO', 'desarrollo') === 'desarrollo';
    }

    private static function leerEnv(string $ruta): array
    {
        if (!is_file($ruta)) {
            return [];
        }
        $valores = [];
        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            $valores[$clave] = trim($valor, "\"'");
        }
        return $valores;
    }
}
