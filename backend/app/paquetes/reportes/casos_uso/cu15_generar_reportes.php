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
use function App\Paquetes\Reportes\Repository\{reporteEstados, reporteEspecialidades, reporteMedicos, reporteAsistencia, reporteOcupacion};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu15GenerarReportes
{
    /** GET /api/v1/reportes/{tipo} — Generar reporte */
    public static function generar(Peticion $p): mixed
    {
        Sesion::requerir('reporte.generar');

        $tipo = trim($p->param('tipo', ''));
        $desde = $p->consulta('desde') ?? date('Y-m-01');
        $hasta = $p->consulta('hasta') ?? date('Y-m-d');
        $idMedico = $p->consulta('id_medico') ? (int) $p->consulta('id_medico') : null;
        $idEspecialidad = $p->consulta('id_especialidad') ? (int) $p->consulta('id_especialidad') : null;

        if (!in_array($tipo, ['estados', 'especialidades', 'medicos', 'asistencia', 'ocupacion'])) {
            throw new ErrorApi(400, 'Tipo de reporte inválido.');
        }
        if ($desde > $hasta) {
            throw new ErrorApi(400, 'Fecha inicial no puede ser mayor que fecha final.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();
        registrarBitacora($s['id_usuario'], 'REPORTE_GENERAR', 0, "Reporte: $tipo", 'reporte', $ip);

        $datos = match ($tipo) {
            'estados' => reporteEstados($desde, $hasta, $idMedico, $idEspecialidad),
            'especialidades' => reporteEspecialidades($desde, $hasta, $idMedico),
            'medicos' => reporteMedicos($desde, $hasta, $idEspecialidad),
            'asistencia' => reporteAsistencia($desde, $hasta, $idMedico, $idEspecialidad),
            'ocupacion' => reporteOcupacion($desde, $hasta, $idMedico, $idEspecialidad),
        };

        $resumen = self::calcularResumen($datos, $tipo);

        return [
            'tipo' => $tipo,
            'datos' => $datos,
            'resumen' => $resumen,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /** GET /api/v1/reportes/{tipo}/csv — Exportar reporte a CSV */
    public static function exportarCsv(Peticion $p): mixed
    {
        Sesion::requerir('reporte.generar');

        $tipo = trim($p->param('tipo', ''));
        $desde = $p->consulta('desde') ?? date('Y-m-01');
        $hasta = $p->consulta('hasta') ?? date('Y-m-d');
        $idMedico = $p->consulta('id_medico') ? (int) $p->consulta('id_medico') : null;
        $idEspecialidad = $p->consulta('id_especialidad') ? (int) $p->consulta('id_especialidad') : null;

        if (!in_array($tipo, ['estados', 'especialidades', 'medicos', 'asistencia', 'ocupacion'])) {
            throw new ErrorApi(400, 'Tipo de reporte inválido.');
        }

        $datos = match ($tipo) {
            'estados' => reporteEstados($desde, $hasta, $idMedico, $idEspecialidad),
            'especialidades' => reporteEspecialidades($desde, $hasta, $idMedico),
            'medicos' => reporteMedicos($desde, $hasta, $idEspecialidad),
            'asistencia' => reporteAsistencia($desde, $hasta, $idMedico, $idEspecialidad),
            'ocupacion' => reporteOcupacion($desde, $hasta, $idMedico, $idEspecialidad),
        };

        $csv = self::generarCsv($datos, $tipo);

        // Nombre del archivo con formato desde_hasta
        $desdeFormato = str_replace('-', '', $desde);
        $hastaFormato = str_replace('-', '', $hasta);
        $nombreArchivo = "reporte_${tipo}_${desdeFormato}_${hastaFormato}.csv";

        header('Content-Type: text/csv; charset=utf-8-sig');
        header("Content-Disposition: attachment; filename=\"${nombreArchivo}\"");
        echo "\xEF\xBB\xBF"; // BOM UTF-8
        echo $csv;
        exit;
    }

    private static function calcularResumen(array $datos, string $tipo): array
    {
        $resumen = [];

        if ($tipo === 'estados') {
            $resumen['total'] = 0;
            foreach ($datos as $row) {
                $resumen['total'] += $row['total'];
                $resumen[$row['estado']] = $row['total'];
            }
        } elseif ($tipo === 'especialidades' || $tipo === 'medicos' || $tipo === 'ocupacion') {
            $resumen['total'] = 0;
            $resumen['atendidas'] = 0;
            $resumen['canceladas'] = 0;
            $resumen['inasistencias'] = 0;

            foreach ($datos as $row) {
                $resumen['total'] += $row['total'];
                if (isset($row['atendidas'])) $resumen['atendidas'] += $row['atendidas'];
                if (isset($row['canceladas'])) $resumen['canceladas'] += $row['canceladas'];
                if (isset($row['inasistencias'])) $resumen['inasistencias'] += $row['inasistencias'];
            }
        } elseif ($tipo === 'asistencia') {
            // Reporte de asistencia ya agrupado por médico con porcentaje calculado
            $resumen['total'] = 0;
            $resumen['atendidas'] = 0;
            $resumen['inasistencias'] = 0;

            foreach ($datos as $row) {
                $resumen['total'] += $row['total'];
                if (isset($row['atendidas'])) $resumen['atendidas'] += $row['atendidas'];
                if (isset($row['inasistencias'])) $resumen['inasistencias'] += $row['inasistencias'];
            }

            // Porcentaje de asistencia general
            $totalParaAsistencia = $resumen['atendidas'] + $resumen['inasistencias'];
            $resumen['porcentaje_asistencia'] = $totalParaAsistencia > 0
                ? round(($resumen['atendidas'] / $totalParaAsistencia) * 100, 1)
                : 0;
        }

        return $resumen;
    }

    private static function generarCsv(array $datos, string $tipo): string
    {
        $csv = '';

        if (empty($datos)) return $csv;

        $mapeoEncabezados = [
            'medico' => 'Médico',
            'especialidad' => 'Especialidad',
            'estado' => 'Estado',
            'fecha' => 'Fecha',
            'hora' => 'Hora',
            'hora_inicio' => 'Hora',
            'paciente' => 'Paciente',
            'codigo' => 'Código',
            'total' => 'Total',
            'atendidas' => 'Atendidas',
            'canceladas' => 'Canceladas',
            'inasistencias' => 'Inasistencias',
            'pendientes' => 'Pendientes',
            'porcentaje_asistencia' => '% Asistencia',
        ];

        $primeraFila = $datos[0];
        $encabezados = array_keys($primeraFila);
        $encabezadosTraducidos = array_map(fn($k) => $mapeoEncabezados[$k] ?? $k, $encabezados);
        $csv .= implode(';', $encabezadosTraducidos) . "\n";

        foreach ($datos as $row) {
            $valores = [];
            foreach ($encabezados as $campo) {
                $valor = $row[$campo] ?? '';

                // Formatear fechas a DD/MM/AAAA
                if ($campo === 'fecha' && $valor) {
                    $partes = explode('-', $valor);
                    if (count($partes) === 3) {
                        $valor = "{$partes[2]}/{$partes[1]}/{$partes[0]}";
                    }
                }

                // Formatear horas a HH:MM
                if (($campo === 'hora' || $campo === 'hora_inicio') && $valor && strlen($valor) >= 5) {
                    $valor = substr($valor, 0, 5);
                }

                $valor = str_replace(';', ',', (string) $valor);
                $valores[] = '"' . str_replace('"', '""', $valor) . '"';
            }
            $csv .= implode(';', $valores) . "\n";
        }

        return $csv;
    }
}
