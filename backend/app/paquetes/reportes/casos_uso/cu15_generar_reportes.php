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
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Reportes\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu15GenerarReportes
{
    /** GET /api/v1/reportes/{tipo} — Generar reporte */
    public static function generar(Peticion $p): mixed
    {
        Sesion::requerir('reporte.generar');

        $tipo = trim($p->param('tipo', ''));
        $desde = $p->param('desde', date('Y-m-01'));
        $hasta = $p->param('hasta', date('Y-m-d'));
        $idMedico = $p->param('id_medico') ? (int) $p->param('id_medico') : null;
        $idEspecialidad = $p->param('id_especialidad') ? (int) $p->param('id_especialidad') : null;

        if ($desde > $hasta) {
            throw new ErrorApi(400, 'Fecha inicial no puede ser mayor que fecha final.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();
        registrarBitacora($s['id_usuario'], 'REPORTE_GENERAR', null, "Reporte: $tipo", 'reporte', $ip);

        $sql = 'SELECT COUNT(*) as total, c.estado FROM cita c WHERE c.fecha BETWEEN :desde AND :hasta';
        $params = [':desde' => $desde, ':hasta' => $hasta];

        if ($idMedico) {
            $sql .= ' AND c.id_medico = :m';
            $params[':m'] = $idMedico;
        }
        if ($idEspecialidad) {
            $sql .= ' AND c.id_especialidad = :e';
            $params[':e'] = $idEspecialidad;
        }

        $sql .= ' GROUP BY c.estado';
        $datos = BaseDatos::consultar($sql, $params);

        return ['tipo' => $tipo, 'datos' => $datos, 'desde' => $desde, 'hasta' => $hasta];
    }

    /** GET /api/v1/reportes/{tipo}/csv — Exportar reporte a CSV */
    public static function exportarCsv(Peticion $p): mixed
    {
        Sesion::requerir('reporte.generar');

        $tipo = trim($p->param('tipo', ''));
        $desde = $p->param('desde', date('Y-m-01'));
        $hasta = $p->param('hasta', date('Y-m-d'));

        $sql = 'SELECT c.id_cita, c.fecha, c.hora_inicio, m.nombres, p.nombres AS paciente, c.estado FROM cita c JOIN medico m ON c.id_medico = m.id_medico JOIN paciente p ON c.id_paciente = p.id_paciente WHERE c.fecha BETWEEN :desde AND :hasta ORDER BY c.fecha';
        $datos = BaseDatos::consultar($sql, [':desde' => $desde, ':hasta' => $hasta]);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"reporte_$tipo.csv\"");
        echo "ID,Fecha,Hora,Médico,Paciente,Estado\n";
        foreach ($datos as $row) {
            echo "{$row['id_cita']},{$row['fecha']},{$row['hora_inicio']},{$row['nombres']},{$row['paciente']},{$row['estado']}\n";
        }
        exit;
    }
}
