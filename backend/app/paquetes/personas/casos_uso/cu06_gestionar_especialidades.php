<?php
/**
 * CU06 - Gestionar especialidades
 * Paquete: personas  ·  Ciclo 1  ·  Prioridad Media  ·  Requisito: RF04
 *
 * Administrar las especialidades que ofrece la clínica (medicina general, pediatría, ginecología,
 * odontología, cardiología, etc.).
 *
 * Actores ........: Administrador
 * Permiso ........: especialidad.gestionar
 * Tablas .........: especialidad, medico_especialidad
 *
 * Precondición:
 *     El administrador inició sesión (CU01).
 *
 * Flujo principal:
 *     1. El administrador ingresa a la opción «Especialidades».
 *     2. El sistema lista las especialidades con su estado.
 *     3. El administrador registra una especialidad con nombre y descripción opcional.
 *     4. El sistema normaliza el nombre (sin espacios extremos y sin distinguir mayúsculas), verifica que no exista y guarda.
 *     5. El administrador puede modificar la descripción o activar/desactivar la especialidad.
 *
 * Excepciones / reglas que debe controlar:
 *     - Nombre normalizado duplicado (p. ej. «PEDIATRÍA» ya existe como «Pediatría»).
 *     - Nombre vacío.
 *     - Desactivar una especialidad con médicos activos: se solicita confirmación.
 *
 * Postcondición:
 *     La especialidad queda disponible para asignarla a médicos y programar citas.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu06GestionarEspecialidades
{
    /** GET /api/v1/personas/especialidades — Listar especialidades */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        throw new NoImplementado('CU06');
    }
    /** POST /api/v1/personas/especialidades — Registrar especialidad */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        throw new NoImplementado('CU06');
    }
    /** PUT /api/v1/personas/especialidades/{id} — Modificar especialidad */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        throw new NoImplementado('CU06');
    }
    /** PATCH /api/v1/personas/especialidades/{id}/estado — Activar o desactivar especialidad */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('especialidad.gestionar');
        throw new NoImplementado('CU06');
    }
}
