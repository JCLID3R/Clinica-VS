<?php
/**
 * CU08 - Consultar disponibilidad de fichas
 * Paquete: agenda  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF06
 *
 * Consultar los horarios disponibles (fichas libres) de un médico o especialidad en una fecha.
 * Una ficha es un intervalo de tiempo (duracion_cita) dentro de un bloque de horario.
 * Se muestra agrupado por médico y turno (Mañana/Tarde).
 *
 * Actores ........: Administrador, Recepcionista, Paciente (Ciclo 2)
 * Permiso ........: cita.programar
 * Tablas .........: medico, especialidad, horario_atencion, cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01).
 *
 * Flujo principal:
 *     1. El usuario especifica especialidad, médico (opcional), y fecha.
 *     2. El sistema calcula las fichas disponibles en ese período.
 *     3. El sistema retorna fichas agrupadas por médico y turno.
 *
 * Excepciones / reglas que debe controlar:
 *     - Fecha no puede ser en el pasado.
 *     - Solo mostrar bloques activos de médicos activos.
 *     - Una ficha está ocupada si hay cita Programada/Confirmada/Atendida en ese intervalo.
 *     - Ocultar horas pasadas del día actual.
 *     - Citas canceladas no ocupan espacio.
 *
 * Postcondición:
 *     Se retorna el listado de fichas disponibles para seleccionar en CU09.
 *
 * Flujo de código: router.php -> este caso de uso -> repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\CasosUso;

use App\Nucleo\{ErrorApi, Peticion, Sesion};
use function App\Paquetes\Agenda\Repository\{
    medicosPorEspecialidad, horariosDisponiblesPorMedico, citasPorMedicoYFecha
};
use function App\Paquetes\Personas\Repository\{obtenerEspecialidadPorId};

final class Cu08ConsultarDisponibilidad
{
    /** GET /api/v1/agenda/especialidades/{id}/medicos — Médicos activos de una especialidad */
    public static function medicosPorEspecialidad(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');
        $idEspecialidad = (int) ($p->param('id') ?? 0);
        if (!$idEspecialidad) {
            throw new ErrorApi(400, 'ID de especialidad requerido.');
        }

        $especialidad = obtenerEspecialidadPorId($idEspecialidad);
        if (!$especialidad) {
            throw new ErrorApi(404, 'Especialidad no encontrada.');
        }

        $medicos = medicosPorEspecialidad($idEspecialidad);

        return [
            'especialidad' => $especialidad,
            'medicos' => $medicos,
        ];
    }

    /** GET /api/v1/agenda/disponibilidad — Horarios libres */
    public static function horariosDisponibles(Peticion $p): mixed
    {
        Sesion::requerir('cita.programar');

        $idEspecialidad = (int) ($p->consulta('id_especialidad') ?? 0);
        if (!$idEspecialidad) {
            throw new ErrorApi(400, 'ID de especialidad requerido.');
        }

        $idMedico = (int) ($p->consulta('id_medico') ?? 0);
        $fechaStr = trim($p->consulta('fecha') ?? '');

        if (!$fechaStr || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaStr)) {
            throw new ErrorApi(400, 'Fecha requerida en formato YYYY-MM-DD.');
        }

        $hoy = date('Y-m-d');
        if ($fechaStr < $hoy) {
            throw new ErrorApi(400, 'No se puede consultar disponibilidad en fechas pasadas.');
        }

        $especialidad = obtenerEspecialidadPorId($idEspecialidad);
        if (!$especialidad) {
            throw new ErrorApi(404, 'Especialidad no encontrada.');
        }

        $medicos = medicosPorEspecialidad($idEspecialidad);
        if (!$medicos) {
            throw new ErrorApi(404, 'No hay médicos activos en esa especialidad.');
        }

        if ($idMedico) {
            $medicos = array_filter($medicos, fn($m) => $m['id_medico'] === $idMedico);
            if (!$medicos) {
                throw new ErrorApi(404, 'Médico no encontrado en esa especialidad.');
            }
        }

        $diaSemana = (int) date('N', strtotime($fechaStr));
        $esHoy = $fechaStr === $hoy;
        $horaActual = $esHoy ? date('H:i') : '00:00';

        $resultado = [];
        foreach ($medicos as $medico) {
            $bloques = horariosDisponiblesPorMedico($medico['id_medico'], $diaSemana);
            $citas = citasPorMedicoYFecha($medico['id_medico'], $fechaStr);

            if (!$bloques) {
                continue;
            }

            $fichasPorTurno = [];
            foreach ($bloques as $bloque) {
                $fichas = self::generarFichas($bloque, $citas, $esHoy, $horaActual);
                if (!$fichas) {
                    continue;
                }

                $turno = self::calcularTurno($bloque['hora_inicio']);
                if (!isset($fichasPorTurno[$turno])) {
                    $fichasPorTurno[$turno] = [];
                }
                $fichasPorTurno[$turno] = array_merge($fichasPorTurno[$turno], $fichas);
            }

            if ($fichasPorTurno) {
                $resultado[] = [
                    'id_medico' => $medico['id_medico'],
                    'nombres' => $medico['nombres'],
                    'apellidos' => $medico['apellidos'],
                    'registro_profesional' => $medico['registro_profesional'],
                    'turnos' => $fichasPorTurno,
                ];
            }
        }

        return [
            'fecha' => $fechaStr,
            'especialidad' => $especialidad,
            'medicos' => $resultado,
        ];
    }

    private static function calcularTurno(string $horaInicio): string
    {
        $hora = (int) explode(':', $horaInicio)[0];
        return $hora < 12 ? 'Mañana' : 'Tarde';
    }

    private static function generarFichas(array $bloque, array $citas, bool $esHoy, string $horaActual): array
    {
        $horaInicioStr = $bloque['hora_inicio'];
        $horaFinStr = $bloque['hora_fin'];
        $duracion = $bloque['duracion_cita'];

        list($horaI, $minI) = explode(':', $horaInicioStr);
        list($horaF, $minF) = explode(':', $horaFinStr);

        $minInicio = (int)$horaI * 60 + (int)$minI;
        $minFin = (int)$horaF * 60 + (int)$minF;
        $minActual = 0;

        if ($esHoy) {
            list($hActual, $mActual) = explode(':', $horaActual);
            $minActual = (int)$hActual * 60 + (int)$mActual;
        }

        $fichas = [];
        $numFicha = 1;

        for ($minuto = $minInicio; $minuto + $duracion <= $minFin; $minuto += $duracion) {
            $hora = (int)($minuto / 60);
            $min = $minuto % 60;
            $horaFichaPasada = $esHoy && $minuto + $duracion <= $minActual;

            if ($horaFichaPasada) {
                $numFicha++;
                continue;
            }

            $horaFicha = str_pad((string)$hora, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$min, 2, '0', STR_PAD_LEFT);
            $horaFichaFin = str_pad((string)((int)($minuto + $duracion) / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)(($minuto + $duracion) % 60), 2, '0', STR_PAD_LEFT);

            $ocupada = false;
            foreach ($citas as $cita) {
                if (self::horariosSeSolapan($horaFicha, $horaFichaFin, $cita['hora_inicio'], $cita['hora_fin'])) {
                    $ocupada = true;
                    break;
                }
            }

            $fichas[] = [
                'numero' => $numFicha,
                'hora' => $horaFicha,
                'hora_fin' => $horaFichaFin,
                'disponible' => !$ocupada,
            ];

            $numFicha++;
        }

        return $fichas;
    }

    private static function horariosSeSolapan(string $ini1, string $fin1, string $ini2, string $fin2): bool
    {
        return $ini1 < $fin2 && $fin1 > $ini2;
    }
}
