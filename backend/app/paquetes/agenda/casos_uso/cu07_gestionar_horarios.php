<?php
/**
 * CU07 - Gestionar horarios de atención
 * Paquete: agenda  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF05
 *
 * Registrar los bloques de atención semanales de cada médico (día, hora inicial, hora final y
 * duración de la cita).
 *
 * Actores ........: Administrador
 * Permiso ........: horario.gestionar
 * Tablas .........: medico, horario_atencion
 *
 * Precondición:
 *     El administrador inició sesión (CU01). El médico está registrado y activo (CU05).
 *
 * Flujo principal:
 *     1. El administrador ingresa a «Horarios de atención» y selecciona un médico activo.
 *     2. El sistema muestra los bloques semanales registrados del médico.
 *     3. El administrador agrega un bloque indicando día de la semana, hora inicial, hora final y duración de la cita.
 *     4. El sistema valida y lo guarda.
 *     5. El sistema muestra la vista previa de los intervalos disponibles.
 *
 * Excepciones / reglas que debe controlar:
 *     - Hora final menor o igual a la hora inicial.
 *     - Bloque superpuesto con otro horario del mismo médico.
 *     - Duración no admitida (valores permitidos: 15, 20, 30, 45 o 60 minutos).
 *     - Rango no divisible por duración.
 *     - Si hay citas futuras en el bloque al modificar/desactivar: aviso y lista sin cambio.
 *
 * Postcondición:
 *     El horario queda registrado y se usa para calcular la disponibilidad (CU08).
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\CasosUso;

use App\Nucleo\{BaseDatos, ErrorApi, Peticion, Sesion};
use function App\Paquetes\Agenda\Repository\{
    horarioListarPorMedico, horarioExisteSuperposicion, horarioInsertar, horarioModificar,
    horarioCambiarEstado, horarioObtener, horarioObtenerCitasFuturas, registrarBitacora
};
use function App\Paquetes\Personas\Repository\{obtenerMedicoConEspecialidades};
use function App\Paquetes\Agenda\Schemas\{
    validar_agregar_horario, validar_modificar_horario, validar_cambiar_estado_horario
};
use function App\Paquetes\Seguridad\Politicas\{ipCliente};

final class Cu07GestionarHorarios
{
    /** GET /api/v1/agenda/medicos/{id}/horarios — Horario semanal de un médico */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        $idMedico = (int) ($p->param('id') ?? 0);
        if (!$idMedico) {
            throw new ErrorApi(400, 'ID de médico requerido.');
        }

        $medico = obtenerMedicoConEspecialidades($idMedico);
        if (!$medico) {
            throw new ErrorApi(404, 'Médico no encontrado.');
        }

        $horarios = horarioListarPorMedico($idMedico);

        return [
            'medico' => $medico,
            'horarios' => $horarios,
        ];
    }

    /** POST /api/v1/agenda/medicos/{id}/horarios — Agregar bloque de horario */
    public static function agregar(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        $idMedico = (int) ($p->param('id') ?? 0);
        if (!$idMedico) {
            throw new ErrorApi(400, 'ID de médico requerido.');
        }

        $medico = obtenerMedicoConEspecialidades($idMedico);
        if (!$medico) {
            throw new ErrorApi(404, 'Médico no encontrado.');
        }

        $body = $p->cuerpo();
        $body['id_medico'] = $idMedico;
        $datos = validar_agregar_horario($body);

        if (horarioExisteSuperposicion($idMedico, $datos['dia_semana'], $datos['hora_inicio'], $datos['hora_fin'])) {
            throw new ErrorApi(409, 'El horario se superpone con otro bloque del médico ese día.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        try {
            $idHorario = BaseDatos::transaccion(function () use ($datos, $s, $ip): int {
                $idHorario = horarioInsertar(
                    $datos['id_medico'],
                    $datos['dia_semana'],
                    $datos['hora_inicio'],
                    $datos['hora_fin'],
                    $datos['duracion_cita']
                );

                registrarBitacora(
                    $s['id_usuario'],
                    'HORARIO_CREAR',
                    $idHorario,
                    "Bloque agregado: día {$datos['dia_semana']}, {$datos['hora_inicio']}-{$datos['hora_fin']} ({$datos['duracion_cita']} min)",
                    'horario_atencion',
                    $ip
                );

                return $idHorario;
            }, $s['id_usuario']);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario se superpone con otro bloque del médico ese día.');
            }
            throw $e;
        }

        return ['mensaje' => 'Bloque de horario agregado correctamente.', 'id_horario' => $idHorario];
    }

    /** PUT /api/v1/agenda/horarios/{id} — Modificar bloque */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        $id = (int) ($p->param('id') ?? 0);
        if (!$id) {
            throw new ErrorApi(400, 'ID de horario requerido.');
        }

        $horario = horarioObtener($id);
        if (!$horario) {
            throw new ErrorApi(404, 'Bloque de horario no encontrado.');
        }

        $body = $p->cuerpo();
        $body['id_medico'] = (int) $horario['id_medico'];
        $datos = validar_modificar_horario($body);

        if (horarioExisteSuperposicion(
            $datos['id_medico'],
            $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            $id
        )) {
            throw new ErrorApi(409, 'El horario se superpone con otro bloque del médico ese día.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        try {
            BaseDatos::transaccion(function () use ($id, $datos, $s, $ip) {
                horarioModificar($id, $datos['hora_inicio'], $datos['hora_fin'], $datos['duracion_cita']);

                registrarBitacora(
                    $s['id_usuario'],
                    'HORARIO_MODIFICAR',
                    $id,
                    "Bloque modificado: {$datos['hora_inicio']}-{$datos['hora_fin']} ({$datos['duracion_cita']} min)",
                    'horario_atencion',
                    $ip
                );
            }, $s['id_usuario']);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23P01') {
                throw new ErrorApi(409, 'El horario se superpone con otro bloque del médico ese día.');
            }
            throw $e;
        }

        return ['mensaje' => 'Bloque modificado correctamente.'];
    }

    /** PATCH /api/v1/agenda/horarios/{id}/estado — Activar o desactivar bloque */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        $id = (int) ($p->param('id') ?? 0);
        if (!$id) {
            throw new ErrorApi(400, 'ID de horario requerido.');
        }

        $horario = horarioObtener($id);
        if (!$horario) {
            throw new ErrorApi(404, 'Bloque de horario no encontrado.');
        }

        $body = $p->cuerpo();
        $datos = validar_cambiar_estado_horario($body);
        $confirmar = isset($body['confirmar']) && $body['confirmar'] === true;

        if ($datos['estado'] === 'inactivo' && !$confirmar) {
            $citas = horarioObtenerCitasFuturas(
                (int) $horario['id_medico'],
                (int) $horario['dia_semana'],
                (string) $horario['hora_inicio'],
                (string) $horario['hora_fin']
            );
            if (!empty($citas)) {
                throw new ErrorApi(409, 'Este bloque tiene citas futuras programadas.', [
                    'advertencia' => true,
                    'citas' => $citas,
                ]);
            }
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $datos, $s, $ip, $horario) {
            horarioCambiarEstado($id, $datos['estado']);
            registrarBitacora(
                $s['id_usuario'],
                'HORARIO_ESTADO',
                $id,
                "Estado: {$horario['estado']} → {$datos['estado']}",
                'horario_atencion',
                $ip
            );
        }, $s['id_usuario']);

        return ['mensaje' => 'Estado del bloque actualizado correctamente.'];
    }

    /** GET /api/v1/agenda/bloqueos — Fechas bloqueadas (para futuro) */
    public static function listarBloqueos(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new ErrorApi(501, 'No implementado en esta versión.');
    }

    /** POST /api/v1/agenda/bloqueos — Registrar fecha bloqueada (para futuro) */
    public static function registrarBloqueo(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new ErrorApi(501, 'No implementado en esta versión.');
    }
}
