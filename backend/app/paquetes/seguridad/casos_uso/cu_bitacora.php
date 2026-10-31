<?php
/**
 * cu_bitacora.php
 * RNF - Bitácora del sistema
 * Paquete: seguridad  ·  Ciclo 1
 *
 * Registro de auditoría de todas las acciones realizadas en el sistema: inicios de sesión,
 * cambios de usuario, operaciones sobre pacientes, médicos, citas, etc.
 *
 * Actores ........: Administrador
 * Permiso ........: usuario.gestionar
 * Tablas .........: bitacora, usuario
 *
 * Flujo: admin busca, filtra y visualiza o exporta el registro de acciones.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Seguridad\CasosUso;

use App\Nucleo\{Peticion, Sesion};

final class CuBitacora
{
    /**
     * GET /api/v1/seguridad/bitacora — Listar bitácora con filtros y paginación.
     * Parámetros: ?usuario=texto&modulo=tabla&accion=USUARIO_CREAR&desde=YYYY-MM-DD&hasta=YYYY-MM-DD&pagina=1&por_pagina=25
     */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return CuBitacoraImpl::listar($p->consulta('usuario'), $p->consulta('tabla'), $p->consulta('accion'),
            $p->consulta('desde'), $p->consulta('hasta'), $p->consulta('pagina'), $p->consulta('por_pagina'));
    }

    /**
     * GET /api/v1/seguridad/bitacora/csv — Exportar bitácora en CSV.
     */
    public static function exportarCsv(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return CuBitacoraImpl::exportarCsv($p->consulta('usuario'), $p->consulta('tabla'), $p->consulta('accion'),
            $p->consulta('desde'), $p->consulta('hasta'));
    }

    /**
     * GET /api/v1/seguridad/bitacora/resumen — Resumen para el panel.
     * Devuelve: últimas 6 acciones, cantidad de acciones hoy, intentos fallidos y bloqueos 24h.
     */
    public static function resumen(Peticion $p): mixed
    {
        Sesion::requerir('usuario.gestionar');
        return CuBitacoraImpl::resumen();
    }
}

// ---- BITACORA: inicio ----
use App\Nucleo\{BaseDatos, ErrorApi, Respuesta};
use function App\Paquetes\Seguridad\Schemas\validar_listar_bitacora;

final class CuBitacoraImpl
{
    public static function listar($usuario, $tabla, $accion, $desde, $hasta, $pagina, $por_pagina): mixed
    {
        $params = validar_listar_bitacora(['usuario' => $usuario, 'tabla' => $tabla, 'accion' => $accion,
            'desde' => $desde, 'hasta' => $hasta, 'pagina' => $pagina, 'por_pagina' => $por_pagina]);

        $sql = "SELECT b.id_bitacora, b.fecha, b.accion, b.tabla, b.id_registro, b.detalle, b.ip, u.username
                FROM bitacora b
                LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
                WHERE 1=1";
        $binds = [];

        if (!empty($params['usuario'])) {
            $sql .= " AND lower(u.username) ILIKE :usuario";
            $binds[':usuario'] = '%' . strtolower($params['usuario']) . '%';
        }

        if (!empty($params['tabla'])) {
            $sql .= " AND b.tabla = :tabla";
            $binds[':tabla'] = $params['tabla'];
        }

        if (!empty($params['accion'])) {
            $sql .= " AND b.accion = :accion";
            $binds[':accion'] = $params['accion'];
        }

        if (!empty($params['desde'])) {
            $sql .= " AND b.fecha::date >= :desde";
            $binds[':desde'] = $params['desde'];
        }

        if (!empty($params['hasta'])) {
            $sql .= " AND b.fecha::date <= :hasta";
            $binds[':hasta'] = $params['hasta'];
        }

        $sqlTotal = "SELECT COUNT(*) as total FROM bitacora b LEFT JOIN usuario u ON u.id_usuario = b.id_usuario WHERE 1=1";
        $sqlTotalBinds = [];
        // Copia las condiciones
        if (!empty($params['usuario'])) {
            $sqlTotal .= " AND lower(u.username) ILIKE :usuario";
            $sqlTotalBinds[':usuario'] = $binds[':usuario'];
        }
        if (!empty($params['tabla'])) {
            $sqlTotal .= " AND b.tabla = :tabla";
            $sqlTotalBinds[':tabla'] = $binds[':tabla'];
        }
        if (!empty($params['accion'])) {
            $sqlTotal .= " AND b.accion = :accion";
            $sqlTotalBinds[':accion'] = $binds[':accion'];
        }
        if (!empty($params['desde'])) {
            $sqlTotal .= " AND b.fecha::date >= :desde";
            $sqlTotalBinds[':desde'] = $binds[':desde'];
        }
        if (!empty($params['hasta'])) {
            $sqlTotal .= " AND b.fecha::date <= :hasta";
            $sqlTotalBinds[':hasta'] = $binds[':hasta'];
        }

        $total = BaseDatos::fila($sqlTotal, $sqlTotalBinds);
        $totalRegistros = (int) $total['total'];

        $pagina = max(1, (int) ($params['pagina'] ?? 1));
        $porPagina = min(100, max(1, (int) ($params['por_pagina'] ?? 25)));
        $offset = ($pagina - 1) * $porPagina;

        $sql .= " ORDER BY b.fecha DESC, b.id_bitacora DESC LIMIT :limit OFFSET :offset";
        $binds[':limit'] = $porPagina;
        $binds[':offset'] = $offset;

        $filas = BaseDatos::consultar($sql, $binds);

        return [
            'bitacora' => $filas,
            'total' => $totalRegistros,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => (int) ceil($totalRegistros / $porPagina),
        ];
    }

    public static function exportarCsv($usuario, $tabla, $accion, $desde, $hasta): mixed
    {
        $params = validar_listar_bitacora(['usuario' => $usuario, 'tabla' => $tabla, 'accion' => $accion,
            'desde' => $desde, 'hasta' => $hasta]);

        $sql = "SELECT b.id_bitacora, b.fecha, b.accion, b.tabla, b.id_registro, b.detalle, b.ip, u.username
                FROM bitacora b
                LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
                WHERE 1=1";
        $binds = [];

        if (!empty($params['usuario'])) {
            $sql .= " AND lower(u.username) ILIKE :usuario";
            $binds[':usuario'] = '%' . strtolower($params['usuario']) . '%';
        }
        if (!empty($params['tabla'])) {
            $sql .= " AND b.tabla = :tabla";
            $binds[':tabla'] = $params['tabla'];
        }
        if (!empty($params['accion'])) {
            $sql .= " AND b.accion = :accion";
            $binds[':accion'] = $params['accion'];
        }
        if (!empty($params['desde'])) {
            $sql .= " AND b.fecha::date >= :desde";
            $binds[':desde'] = $params['desde'];
        }
        if (!empty($params['hasta'])) {
            $sql .= " AND b.fecha::date <= :hasta";
            $binds[':hasta'] = $params['hasta'];
        }

        $sql .= " ORDER BY b.fecha DESC, b.id_bitacora DESC";
        $filas = BaseDatos::consultar($sql, $binds);

        // CSV con BOM UTF-8 y punto y coma
        $csv = "\xEF\xBB\xBF"; // BOM
        $csv .= "Fecha y hora;Usuario;Acción;Módulo;ID Registro;Detalle;IP\n";

        foreach ($filas as $fila) {
            $csv .= sprintf(
                '"%s";"%s";"%s";"%s";"%s";"%s";"%s"' . "\n",
                self::escaparCsv($fila['fecha']),
                self::escaparCsv($fila['username'] ?? 'Sistema'),
                self::escaparCsv($fila['accion']),
                self::escaparCsv($fila['tabla']),
                self::escaparCsv((string) $fila['id_registro']),
                self::escaparCsv($fila['detalle']),
                self::escaparCsv($fila['ip']),
            );
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bitacora_' . date('Y-m-d_H-i-s') . '.csv"');
        echo $csv;
        exit;
    }

    public static function resumen(): mixed
    {
        // Últimas 6 acciones
        $ultimas = BaseDatos::consultar(
            "SELECT b.fecha, b.accion, u.username
             FROM bitacora b
             LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
             ORDER BY b.fecha DESC, b.id_bitacora DESC LIMIT 6"
        );

        // Cantidad de acciones hoy
        $hoy = BaseDatos::fila(
            "SELECT COUNT(*) as total FROM bitacora
             WHERE fecha::date = CURRENT_DATE"
        );

        // Intentos fallidos y bloqueos en las últimas 24 horas
        $hace24h = BaseDatos::fila(
            "SELECT
                COUNT(CASE WHEN accion = 'INICIO_FALLIDO' THEN 1 END) as intentos_fallidos,
                COUNT(CASE WHEN accion = 'BLOQUEO_INTENTOS' THEN 1 END) as bloqueos
             FROM bitacora
             WHERE fecha >= now() - INTERVAL '24 hours'"
        );

        return [
            'ultimas_acciones' => $ultimas,
            'acciones_hoy' => (int) $hoy['total'],
            'intentos_fallidos_24h' => (int) $hace24h['intentos_fallidos'],
            'bloqueos_24h' => (int) $hace24h['bloqueos'],
        ];
    }

    private static function escaparCsv(string $valor): string
    {
        // Si contiene comillas, duplicarlas
        return str_replace('"', '""', $valor);
    }
}
// ---- BITACORA: fin ----
