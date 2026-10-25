<?php
/**
 * CU07 - Gestionar horarios de atención
 * Paquete: agenda  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF05
 *
 * Registrar los bloques de atención semanales de cada médico (día, hora inicial, hora final y
 * duración de la cita) y las fechas bloqueadas o feriados.
 *
 * Actores ........: Administrador
 * Permiso ........: horario.gestionar
 * Tablas .........: medico, horario_atencion, bloqueo_agenda
 *
 * Precondición:
 *     El administrador inició sesión (CU01). El médico está registrado y activo (CU05).
 *
 * Flujo principal:
 *     1. El administrador ingresa a «Horarios de atención» y selecciona un médico activo.
 *     2. El sistema muestra los bloques semanales registrados del médico.
 *     3. El administrador agrega un bloque indicando día de la semana, hora inicial, hora final y duración de la cita (p. ej. 30 minutos).
 *     4. El sistema valida que la hora final sea posterior a la inicial y que el bloque no se superponga con otro del mismo médico y día, y lo guarda.
 *     5. Opcionalmente, el administrador registra una fecha bloqueada (vacación o feriado) para el médico o para toda la clínica.
 *     6. El sistema muestra la vista previa de los intervalos que se ofrecerán.
 *
 * Excepciones / reglas que debe controlar:
 *     - Hora final menor o igual a la hora inicial.
 *     - Bloque superpuesto con otro horario del mismo médico.
 *     - Duración no admitida (valores permitidos: 15, 20, 30, 45 o 60 minutos).
 *
 * Postcondición:
 *     El horario queda registrado y se usa para calcular la disponibilidad (CU08).
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Agenda\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu07GestionarHorarios
{
    /** GET /api/v1/agenda/medicos/{id}/horarios — Horario semanal de un médico */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new NoImplementado('CU07');
    }
    /** POST /api/v1/agenda/medicos/{id}/horarios — Agregar bloque de horario */
    public static function agregar(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new NoImplementado('CU07');
    }
    /** PUT /api/v1/agenda/horarios/{id} — Modificar bloque */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new NoImplementado('CU07');
    }
    /** PATCH /api/v1/agenda/horarios/{id}/estado — Activar o desactivar bloque */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new NoImplementado('CU07');
    }
    /** GET /api/v1/agenda/bloqueos — Fechas bloqueadas */
    public static function listarBloqueos(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new NoImplementado('CU07');
    }
    /** POST /api/v1/agenda/bloqueos — Registrar fecha bloqueada */
    public static function registrarBloqueo(Peticion $p): mixed
    {
        Sesion::requerir('horario.gestionar');
        throw new NoImplementado('CU07');
    }
}
