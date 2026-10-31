<?php
/**
 * CU Panel - Resumen del sistema para el dashboard principal
 * Paquete: panel  ·  Ciclo 1
 *
 * Calcula indicadores clave: citas hoy, estados, pacientes, médicos y bitácora.
 *
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Panel\CasosUso;

use App\Nucleo\BaseDatos;

final class CuPanel
{
    /** GET /api/v1/panel/resumen — Resumen de indicadores para el panel principal */
    public static function resumen(): mixed
    {
        $hoy = date('Y-m-d');

        $citasHoy = BaseDatos::consultar(
            "SELECT COUNT(*) as total FROM v_agenda_medica
             WHERE fecha = :fecha AND estado != 'Cancelada'",
            [':fecha' => $hoy]
        );

        $estadosHoy = BaseDatos::fila(
            "SELECT COUNT(*) FILTER (WHERE estado = 'Programada') as programadas,
                    COUNT(*) FILTER (WHERE estado = 'Atendida') as atendidas,
                    COUNT(*) FILTER (WHERE estado = 'Cancelada') as canceladas
             FROM v_agenda_medica
             WHERE fecha = :fecha",
            [':fecha' => $hoy]
        );

        $pacientes = BaseDatos::fila(
            "SELECT COUNT(*) as total FROM paciente WHERE estado = 'activo'"
        );

        $medicos = BaseDatos::fila(
            "SELECT COUNT(*) as total FROM medico WHERE estado = 'activo'"
        );

        $citasDetalle = BaseDatos::consultar(
            "SELECT codigo, paciente, medico, hora_inicio, estado FROM v_agenda_medica
             WHERE fecha = :fecha AND estado != 'Cancelada'
             ORDER BY hora_inicio ASC",
            [':fecha' => $hoy]
        );

        $bitacora = BaseDatos::consultar(
            "SELECT b.fecha, b.accion, b.tabla, u.username
             FROM bitacora b
             LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
             ORDER BY b.fecha DESC LIMIT 5"
        );

        return [
            'citas_hoy' => (int)($citasHoy[0]['total'] ?? 0),
            'programadas' => (int)($estadosHoy['programadas'] ?? 0),
            'atendidas' => (int)($estadosHoy['atendidas'] ?? 0),
            'canceladas' => (int)($estadosHoy['canceladas'] ?? 0),
            'pacientes_total' => (int)($pacientes['total'] ?? 0),
            'medicos_activos' => (int)($medicos['total'] ?? 0),
            'citas_detalle' => $citasDetalle,
            'bitacora' => $bitacora,
        ];
    }
}
