<?php
/**
 * CU15 - Generar reportes
 * Paquete: reportes  ·  Ciclo 2  ·  Prioridad Media  ·  Requisito: RF13
 *
 * Generar los reportes administrativos: citas por fecha, por médico, por especialidad y por estado;
 * pacientes registrados; cantidad de citas atendidas, canceladas e inasistencias en un período; y
 * agenda diaria de cada médico.
 *
 * Actores ........: Administrador, Recepcionista
 * Permiso ........: reporte.generar
 * Tablas .........: cita, medico, especialidad, medico_especialidad, paciente
 *
 * Precondición:
 *     El usuario inició sesión (CU01) y tiene el permiso reporte.generar.
 *
 * Flujo principal:
 *     1. El usuario ingresa a «Reportes» y elige el tipo de reporte.
 *     2. Selecciona los filtros: fecha o rango de fechas, médico, especialidad y/o estado.
 *     3. El sistema valida que la fecha inicial no sea mayor que la final y ejecuta la consulta parametrizada.
 *     4. El sistema muestra el reporte en una tabla con totales.
 *     5. Opcionalmente, el usuario exporta el reporte a CSV o PDF, o lo imprime.
 *
 * Excepciones / reglas que debe controlar:
 *     - Rango de fechas inválido.
 *     - No existen datos para los filtros seleccionados.
 *
 * Postcondición:
 *     Se obtiene el reporte solicitado con sus totales.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Reportes\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu15GenerarReportes
{
    /** GET /api/v1/reportes/{tipo} — Generar reporte (?desde=&hasta=&id_medico=&id_especialidad=&estado=) */
    public static function generar(Peticion $p): mixed
    {
        Sesion::requerir('reporte.generar');
        throw new NoImplementado('CU15');
    }
    /** GET /api/v1/reportes/{tipo}/csv — Exportar reporte a CSV */
    public static function exportarCsv(Peticion $p): mixed
    {
        Sesion::requerir('reporte.generar');
        throw new NoImplementado('CU15');
    }
}
