<?php
/**
 * Endpoints del paquete reportes: /api/v1/...
 * Cada endpoint llama a su caso de uso en casos_uso/.
 */
declare(strict_types=1);

use App\Nucleo\Enrutador;
use App\Paquetes\Reportes\CasosUso\Cu15GenerarReportes;

return static function (Enrutador $r): void {
    $r->get('/reportes/{tipo}', [Cu15GenerarReportes::class, 'generar'], 'CU15 - Generar reporte (?desde=&hasta=&id_medico=&id_especialidad=&estado=)');
    $r->get('/reportes/{tipo}/csv', [Cu15GenerarReportes::class, 'exportarCsv'], 'CU15 - Exportar reporte a CSV');
};
