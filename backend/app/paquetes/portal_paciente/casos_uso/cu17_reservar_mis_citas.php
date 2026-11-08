<?php
/**
 * CU17 - Reservar y dar seguimiento a mis citas
 * Paquete: portal_paciente  ·  Ciclo 2  ·  Prioridad Media  ·  Requisito: Perfil del paciente (3.4)
 *
 * Permitir que el paciente reserve una cita en línea buscando médicos por especialidad y
 * disponibilidad, y dé seguimiento a sus citas pendientes, confirmadas, completadas y canceladas.
 *
 * Actores ........: Paciente
 * Permiso ........: perfil.paciente
 * Tablas .........: medico, horario_atencion, cita
 *
 * Precondición:
 *     El paciente inició sesión en el portal (CU01) y su registro está activo.
 *
 * Flujo principal:
 *     1. El paciente ingresa a «Reservar cita».
 *     2. Selecciona especialidad, médico y fecha; el sistema muestra los horarios disponibles (CU08).
 *     3. Elige un horario, la modalidad y un motivo breve, y confirma.
 *     4. El sistema registra la cita con las mismas validaciones del servidor que CU09.
 *     5. En «Mis citas», el sistema lista sus citas por estado y muestra un recordatorio de las próximas consultas.
 *     6. Desde «Mis citas» el paciente puede cancelar una cita programada (CU11).
 *
 * Excepciones / reglas que debe controlar:
 *     - El horario fue ocupado por otra reserva.
 *     - El paciente ya tiene una cita pendiente con el mismo médico en la misma fecha.
 *
 * Postcondición:
 *     La cita queda «Programada» a nombre del paciente; el paciente ve el estado de todas sus citas.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\PortalPaciente\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Personas\Repository\{
    pacientePorUsuario, listarEspecialidades, obtenerEspecialidadPorId
};
use function App\Paquetes\Agenda\Repository\{
    medicosPorEspecialidad, horariosDisponiblesPorMedico, citasPorMedicoYFecha, registrarBitacora
};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu17ReservarMisCitas
{
    /** GET /api/v1/portal/especialidades — Listar especialidades activas */
    public static function listarEspecialidades(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $resultado = listarEspecialidades('', 'activo', 1, 100);
        return ['especialidades' => $resultado['especialidades']];
    }

    /** GET /api/v1/portal/especialidades/{id}/medicos — Médicos activos de una especialidad */
    public static function medicosPorEspecialidad(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
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

    /** GET /api/v1/portal/disponibilidad — Horarios libres */
    public static function horariosDisponibles(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');

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
            $horaFichaFin = str_pad((string)intdiv($minuto + $duracion, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)(($minuto + $duracion) % 60), 2, '0', STR_PAD_LEFT);

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

    /** GET /api/v1/portal/citas — Mis citas separadas en próximas e historial */
    public static function misCitas(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $citas = BaseDatos::consultar(
            "SELECT c.id_cita, 'CM-' || lpad(c.id_cita::text, 4, '0') AS codigo, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
                    c.id_medico, c.id_especialidad,
                    m.nombres || ' ' || m.apellidos AS medico, e.nombre AS especialidad,
                    p.nombres || ' ' || p.apellidos AS paciente, p.ci
             FROM cita c
             JOIN medico m ON c.id_medico = m.id_medico
             JOIN especialidad e ON c.id_especialidad = e.id_especialidad
             JOIN paciente p ON c.id_paciente = p.id_paciente
             WHERE c.id_paciente = :p
             ORDER BY c.fecha DESC, c.hora_inicio DESC",
            [':p' => $paciente['id_paciente']]
        );

        $hoy = date('Y-m-d');
        $horaActual = date('H:i:s');
        $proximas = [];
        $historial = [];

        foreach ($citas as $cita) {
            $esFutura = $cita['fecha'] > $hoy || ($cita['fecha'] === $hoy && $cita['hora_inicio'] > $horaActual);
            $esProgramadaOConfirmada = $cita['estado'] === 'Programada' || $cita['estado'] === 'Confirmada';

            if ($esFutura && $esProgramadaOConfirmada) {
                $proximas[] = $cita;
            } else {
                $historial[] = $cita;
            }
        }

        return ['proximas' => $proximas, 'historial' => $historial];
    }

    /** POST /api/v1/portal/citas — Reservar cita en línea */
    public static function reservar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $body = $p->cuerpo();
        $idEspecialidad = (int) ($body['id_especialidad'] ?? 0);
        $idMedico = (int) ($body['id_medico'] ?? 0);
        $fecha = trim($body['fecha'] ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFin = trim($body['hora_fin'] ?? '');

        if (!$idEspecialidad || !$fecha || !$horaInicio || !$horaFin) {
            throw new ErrorApi(400, 'Campos requeridos.');
        }

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $idCita = null;
        try {
            BaseDatos::transaccion(function () use ($paciente, $idEspecialidad, $idMedico, $fecha, $horaInicio, $horaFin, $s, &$idCita) {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                $result = BaseDatos::fila(
                    "INSERT INTO cita (id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, estado, id_usuario_registro) VALUES (:p, :m, :e, :f, :hi, :hf, 'Programada', :iu) RETURNING id_cita",
                    [':p' => $paciente['id_paciente'], ':m' => $idMedico, ':e' => $idEspecialidad, ':f' => $fecha, ':hi' => $horaInicio, ':hf' => $horaFin, ':iu' => $s['id_usuario']]
                );
                $idCita = (int) $result['id_cita'];
            }, $s['id_usuario']);

            registrarBitacora($s['id_usuario'], 'CITA_RESERVA_PORTAL', $paciente['id_paciente'], "Cita reservada por paciente", 'cita', ipCliente());

            $cita = BaseDatos::fila(
                "SELECT 'CM-' || lpad(c.id_cita::text, 4, '0') AS codigo,
                        p.nombres || ' ' || p.apellidos AS paciente,
                        e.nombre AS especialidad,
                        m.nombres || ' ' || m.apellidos AS medico,
                        c.fecha, c.hora_inicio, c.hora_fin
                 FROM cita c
                 JOIN medico m ON c.id_medico = m.id_medico
                 JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                 JOIN paciente p ON c.id_paciente = p.id_paciente
                 WHERE c.id_cita = :id",
                [':id' => $idCita]
            );

            $comprobante = [
                'codigo' => $cita['codigo'],
                'paciente' => $cita['paciente'],
                'especialidad' => $cita['especialidad'],
                'medico' => $cita['medico'],
                'fecha' => $cita['fecha'],
                'horario' => substr($cita['hora_inicio'], 0, 5) . ' - ' . substr($cita['hora_fin'], 0, 5),
            ];

            return ['mensaje' => 'Cita reservada correctamente.', 'id_cita' => $idCita, 'comprobante' => $comprobante];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario ya no está disponible.');
            }
            throw $e;
        }
    }

    /** PATCH /api/v1/portal/citas/{id}/cancelar — Cancelar mi cita */
    public static function cancelar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $motivo = trim($body['motivo_cancelacion'] ?? '');

        if (!$motivo) throw new ErrorApi(400, 'Motivo de cancelación requerido.');

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $cita = BaseDatos::fila("SELECT * FROM cita WHERE id_cita = :id", [':id' => $idCita]);
        if (!$cita || $cita['id_paciente'] !== $paciente['id_paciente']) {
            throw new ErrorApi(403, 'No puedes cancelar esta cita.');
        }

        if ($cita['estado'] !== 'Programada' && $cita['estado'] !== 'Confirmada') {
            throw new ErrorApi(400, 'Solo citas Programada o Confirmada pueden modificarse.');
        }

        BaseDatos::transaccion(function () use ($idCita, $motivo, $s) {
            BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
            BaseDatos::ejecutar(
                "UPDATE cita SET estado = 'Cancelada', motivo_cancelacion = :m WHERE id_cita = :id",
                [':m' => $motivo, ':id' => $idCita]
            );
            registrarBitacora($s['id_usuario'], 'CITA_CANCELAR_PORTAL', $idCita, "Cita cancelada por paciente: $motivo", 'paciente', ipCliente());
        }, $s['id_usuario']);

        return ['mensaje' => 'Cita cancelada.'];
    }

    /** PATCH /api/v1/portal/citas/{id}/reprogramar — Reprogramar mi cita */
    public static function reprogramar(Peticion $p): mixed
    {
        Sesion::requerir('perfil.paciente');
        $s = Sesion::usuario();

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $fecha = trim($body['fecha'] ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFin = trim($body['hora_fin'] ?? '');

        if (!$fecha || !$horaInicio || !$horaFin) {
            throw new ErrorApi(400, 'Datos incompletos.');
        }

        $paciente = pacientePorUsuario($s['id_usuario']);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $cita = BaseDatos::fila("SELECT * FROM cita WHERE id_cita = :id", [':id' => $idCita]);
        if (!$cita || $cita['id_paciente'] !== $paciente['id_paciente']) {
            throw new ErrorApi(403, 'No puedes reprogramar esta cita.');
        }

        if ($cita['estado'] !== 'Programada' && $cita['estado'] !== 'Confirmada') {
            throw new ErrorApi(400, 'Solo citas Programada o Confirmada pueden modificarse.');
        }

        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            throw new ErrorApi(400, 'La nueva fecha no puede ser en el pasado.');
        }

        try {
            BaseDatos::transaccion(function () use ($idCita, $fecha, $horaInicio, $horaFin, $s) {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                BaseDatos::ejecutar(
                    "UPDATE cita SET fecha = :f, hora_inicio = :hi, hora_fin = :hf WHERE id_cita = :id",
                    [':f' => $fecha, ':hi' => $horaInicio, ':hf' => $horaFin, ':id' => $idCita]
                );
                registrarBitacora($s['id_usuario'], 'CITA_REPROGRAMAR_PORTAL', $idCita, "Cita reprogramada a $fecha $horaInicio", 'paciente', ipCliente());
            }, $s['id_usuario']);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario ya no está disponible.');
            }
            throw $e;
        }

        return ['mensaje' => 'Cita reprogramada.'];
    }
}
