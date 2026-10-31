<?php
/**
 * Panel — Endpoints del dashboard principal
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\Panel\CasosUso\CuPanel;

return static function (Enrutador $r): void {
    $r->get('/panel/resumen', [CuPanel::class, 'resumen'], 'Panel - Resumen de indicadores');
};
