<?php
/**
 * BaseDatos: conexión única a PostgreSQL con PDO (controlador pdo_pgsql).
 *
 * - Consultas siempre preparadas (evita inyección SQL).
 * - Zona horaria America/La_Paz.
 * - transaccion(): ejecuta un bloque dentro de BEGIN/COMMIT y fija el usuario
 *   que realiza la operación (lo usan los triggers de historial/bitácora).
 */
declare(strict_types=1);

namespace App\Nucleo;

use PDO;
use PDOException;

final class BaseDatos
{
    private static ?PDO $pdo = null;

    public static function conexion(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                Config::obtener('DB_HOST', '127.0.0.1'),
                Config::obtener('DB_PUERTO', '5432'),
                Config::obtener('DB_NOMBRE', 'clinica_vida_salud')
            );
            try {
                self::$pdo = new PDO($dsn, Config::obtener('DB_USUARIO', 'postgres'), Config::obtener('DB_CLAVE', ''), [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                self::$pdo->exec("SET TIME ZONE 'America/La_Paz'");
            } catch (PDOException $e) {
                error_log('Error de conexión a la base de datos: ' . $e->getMessage());
                throw new ErrorApi(503, 'No se pudo conectar con la base de datos.');
            }
        }
        return self::$pdo;
    }

    /** SELECT que devuelve todas las filas. */
    public static function consultar(string $sql, array $parametros = []): array
    {
        $st = self::conexion()->prepare($sql);
        $st->execute($parametros);
        return $st->fetchAll();
    }

    /** SELECT que devuelve una fila o null. */
    public static function fila(string $sql, array $parametros = []): ?array
    {
        $st = self::conexion()->prepare($sql);
        $st->execute($parametros);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /** INSERT / UPDATE / DELETE. Devuelve las filas afectadas. */
    public static function ejecutar(string $sql, array $parametros = []): int
    {
        $st = self::conexion()->prepare($sql);
        $st->execute($parametros);
        return $st->rowCount();
    }

    /**
     * Ejecuta $bloque dentro de una transacción. Si algo falla se hace ROLLBACK.
     * @template T
     * @param callable(PDO):T $bloque
     * @return T
     */
    public static function transaccion(callable $bloque, ?int $idUsuario = null): mixed
    {
        $pdo = self::conexion();
        $pdo->beginTransaction();
        try {
            if ($idUsuario !== null) {
                $pdo->prepare("SELECT set_config('app.id_usuario', :u, true)")->execute([':u' => (string) $idUsuario]);
            }
            $resultado = $bloque($pdo);
            $pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
