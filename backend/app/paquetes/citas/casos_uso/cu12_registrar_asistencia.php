<?php
/**
 * CU12 - Registrar asistencia de la cita
 * Paquete: citas  ·  Ciclo 2  ·  Prioridad Alta  ·  Requisito: RF10
 *
 * Controlar los estados de la cita: confirmar la asistencia, marcarla como Atendida o registrar la
 * Inasistencia del paciente, aplicando solo transiciones válidas.
 *
 * Actores ........: Recepcionista, Médico
 * Permiso ........: cita.asistencia
 * Tablas .........: cita, historial_estado_cita
 *
 * Precondición:
 *     El usuario inició sesión (CU01) y tiene el permiso correspondiente (cita.asistencia o cita.atender).
 *
 * Flujo principal:
 *     1. El usuario abre la agenda del día (CU13) y selecciona una cita.
 *     2. El sistema muestra el estado actual y solo las acciones válidas para ese estado.
 *     3. La recepcionista confirma la llegada del paciente («Confirmada») o registra «Inasistencia» si no se presentó.
 *     4. El médico, si tiene el permiso cita.atender, marca la cita como «Atendida» al finalizar la consulta.
 *     5. El sistema valida la transición, actualiza el estado y registra el cambio en el historial con el usuario y la fecha.
 *
 * Excepciones / reglas que debe controlar:
 *     - Transición no permitida (p. ej. Cancelada → Atendida) sin un procedimiento autorizado.
 *     - Marcar Atendida o Inasistencia una cita cuya hora aún no llegó.
 *     - Médico sin permiso cita.atender.
 *
 * Postcondición:
 *     La cita queda en el nuevo estado y el cambio es trazable.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Citas\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Citas\Repository\{citasDelDia, citaObtener, citaCambiarEstado, citasPendientesAnteriores};
use function App\Paquetes\Personas\Repository\{medicoPorUsuario};
use function App\Paquetes\Agenda\Repository\{registrarBitacora};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu12RegistrarAsistencia
{
    /** GET /api/v1/citas/asistencia?fecha=&id_medico=&estado= — Citas del día */
    public static function citasDelDia(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');

        $fecha = $p->param('fecha', date('Y-m-d'));
        $idMedico = $p->param('id_medico') ? (int) $p->param('id_medico') : null;
        $estado = $p->param('estado') ? trim($p->param('estado')) : null;

        $citas = \App\Paquetes\Citas\Repository\citasDelDia($fecha, $idMedico, $estado);

        return ['citas' => $citas, 'fecha' => $fecha];
    }

    /** GET /api/v1/citas/{id}/acciones — Acciones válidas según estado */
    public static function accionesValidas(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');

        $idCita = (int) $p->param('id');
        if (!$idCita) throw new ErrorApi(400, 'ID de cita requerido.');

        $cita = citaObtener($idCita);
        if (!$cita) throw new ErrorApi(404, 'Cita no encontrada.');

        $acciones = [];
        $ahora = new \DateTime();
        $citaTime = new \DateTime($cita['fecha'] . ' ' . $cita['hora_inicio']);
        $yaOcurrio = $ahora > $citaTime;

        if ($cita['estado'] === 'Programada') {
            $acciones[] = ['accion' => 'Confirmar', 'label' => 'Confirmar llegada', 'nuevoEstado' => 'Confirmada'];
            if ($yaOcurrio) {
                $acciones[] = ['accion' => 'Atendida', 'label' => 'Marcar atendida', 'nuevoEstado' => 'Atendida'];
                $acciones[] = ['accion' => 'Inasistencia', 'label' => 'Marcar inasistencia', 'nuevoEstado' => 'Inasistencia'];
            }
        } elseif ($cita['estado'] === 'Confirmada') {
            if ($yaOcurrio) {
                $acciones[] = ['accion' => 'Atendida', 'label' => 'Marcar atendida', 'nuevoEstado' => 'Atendida'];
                $acciones[] = ['accion' => 'Inasistencia', 'label' => 'Marcar inasistencia', 'nuevoEstado' => 'Inasistencia'];
            }
        }

        return ['acciones' => $acciones];
    }

    /** PATCH /api/v1/citas/{id}/estado — Cambiar estado */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');

        $idCita = (int) $p->param('id');
        $body = $p->cuerpo();
        $estado = trim($body['estado'] ?? '');

        if (!$idCita) throw new ErrorApi(400, 'ID de cita requerido.');
        if (!in_array($estado, ['Confirmada', 'Atendida', 'Inasistencia'])) {
            throw new ErrorApi(400, 'Estado inválido.');
        }

        $cita = citaObtener($idCita);
        if (!$cita) throw new ErrorApi(404, 'Cita no encontrada.');

        $s = Sesion::usuario();
        $medico = medicoPorUsuario($s['id_usuario']);
        if ($estado === 'Atendida' && $medico && $cita['id_medico'] !== $medico['id_medico']) {
            throw new ErrorApi(403, 'Solo el médico de la cita puede marcarla como atendida.');
        }

        $ip = ipCliente();

        try {
            BaseDatos::transaccion(function () use ($idCita, $estado, $s) {
                BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                citaCambiarEstado($idCita, $estado);
            }, $s['id_usuario']);

            registrarBitacora(
                $s['id_usuario'],
                'CITA_' . strtoupper($estado),
                $idCita,
                "Cita {$cita['codigo']} marcada como {$estado}",
                'cita',
                $ip
            );

            return ['mensaje' => 'Estado de cita actualizado correctamente.'];
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /** GET /api/v1/citas/inasistencias-pendientes — Citas sin marcar de días pasados */
    public static function inasistenciasPendientes(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');

        $citas = citasPendientesAnteriores();
        return ['citas' => $citas];
    }

    /** POST /api/v1/citas/inasistencias-pendientes — Marcar lote de inasistencias */
    public static function marcarInasistencias(Peticion $p): mixed
    {
        Sesion::requerir('cita.asistencia');

        $body = $p->cuerpo();
        $idsCitas = $body['ids_citas'] ?? [];

        if (!is_array($idsCitas) || empty($idsCitas)) {
            throw new ErrorApi(400, 'Debe seleccionar al menos una cita.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();
        $marcadas = 0;

        try {
            foreach ($idsCitas as $idCita) {
                $idCita = (int) $idCita;
                $cita = citaObtener($idCita);
                if ($cita && in_array($cita['estado'], ['Programada', 'Confirmada'])) {
                    BaseDatos::transaccion(function () use ($idCita, $s) {
                        BaseDatos::ejecutar('SELECT set_config(\'app.id_usuario\', :id, true)', [':id' => (string) $s['id_usuario']]);
                        citaCambiarEstado($idCita, 'Inasistencia');
                    }, $s['id_usuario']);
                    $marcadas++;

                    registrarBitacora(
                        $s['id_usuario'],
                        'CITA_INASISTENCIA_LOTE',
                        $idCita,
                        "Marcada como inasistencia (lote)",
                        'cita',
                        $ip
                    );
                }
            }

            return ['mensaje' => "Se marcaron $marcadas citas como inasistencia."];
        } catch (\PDOException $e) {
            throw $e;
        }
    }
}
