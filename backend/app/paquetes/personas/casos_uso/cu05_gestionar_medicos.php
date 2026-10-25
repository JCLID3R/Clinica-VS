<?php
/**
 * CU05 - Gestionar médicos
 * Paquete: personas  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF03
 *
 * Registrar, consultar, buscar, modificar, activar y desactivar médicos con sus especialidades,
 * conservando sus citas históricas.
 *
 * Actores ........: Administrador
 * Permiso ........: medico.gestionar
 * Tablas .........: medico, especialidad, medico_especialidad
 *
 * Precondición:
 *     El administrador inició sesión (CU01). Existen especialidades activas (CU06).
 *
 * Flujo principal:
 *     1. El administrador ingresa a la opción «Médicos».
 *     2. El sistema lista los médicos con su especialidad y estado; permite buscar por nombre o especialidad.
 *     3. Para registrar, el administrador ingresa número de registro profesional, nombres, apellidos, teléfono, correo y selecciona una o varias especialidades.
 *     4. El sistema valida que el registro profesional no exista, asigna su identificador (id_medico) y guarda el médico y sus especialidades en una transacción.
 *     5. Opcionalmente crea la cuenta de usuario con rol Médico (CU02).
 *     6. Para modificar, actualiza datos o especialidades y guarda.
 *     7. Para desactivar, cambia el estado a «inactivo»; el médico deja de recibir nuevas citas pero sus citas históricas se conservan.
 *
 * Excepciones / reglas que debe controlar:
 *     - Registro profesional duplicado.
 *     - No se seleccionó ninguna especialidad.
 *     - Desactivar un médico con citas futuras programadas: el sistema advierte y lista las citas a reprogramar.
 *
 * Postcondición:
 *     El médico queda registrado o actualizado con sus especialidades.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu05GestionarMedicos
{
    /** GET /api/v1/personas/medicos — Listar y buscar médicos */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        throw new NoImplementado('CU05');
    }
    /** POST /api/v1/personas/medicos — Registrar médico */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        throw new NoImplementado('CU05');
    }
    /** PUT /api/v1/personas/medicos/{id} — Modificar médico y especialidades */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        throw new NoImplementado('CU05');
    }
    /** PATCH /api/v1/personas/medicos/{id}/estado — Activar o desactivar médico */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        throw new NoImplementado('CU05');
    }
}
