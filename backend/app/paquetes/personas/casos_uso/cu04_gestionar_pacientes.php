<?php
/**
 * CU04 - Gestionar pacientes
 * Paquete: personas  ·  Ciclo 1  ·  Prioridad Alta  ·  Requisito: RF02
 *
 * Registrar, listar, buscar, modificar y desactivar pacientes, y consultar sus citas anteriores,
 * sin perder el historial asociado.
 *
 * Actores ........: Recepcionista, Administrador
 * Permiso ........: paciente.gestionar
 * Tablas .........: paciente, contacto_emergencia, cita
 *
 * Precondición:
 *     La recepcionista o el administrador inició sesión (CU01) y tiene el permiso paciente.gestionar.
 *
 * Flujo principal:
 *     1. La recepcionista ingresa a la opción «Pacientes».
 *     2. El sistema lista los pacientes activos y permite buscar por CI, nombre o apellido.
 *     3. Para registrar, la recepcionista selecciona «Nuevo paciente» e ingresa CI, nombres, apellidos, fecha de nacimiento, sexo, teléfono, correo (opcional), dirección (opcional) y contacto de emergencia.
 *     4. El sistema valida los datos en el cliente y en el servidor, verifica que el CI no esté registrado, asigna su identificador (id_paciente) y guarda el paciente.
 *     5. Para modificar, selecciona un paciente, actualiza sus datos y guarda.
 *     6. Para desactivar, selecciona «Desactivar»; el sistema cambia el estado a «inactivo» sin eliminar sus citas.
 *     7. La recepcionista puede seleccionar «Ver citas» para consultar el historial del paciente (CU14).
 *
 * Excepciones / reglas que debe controlar:
 *     - CI ya registrado: el sistema rechaza el duplicado (RN01).
 *     - Campos obligatorios vacíos o con formato inválido (teléfono, correo, fecha de nacimiento futura).
 *     - Paciente no encontrado en la búsqueda.
 *
 * Postcondición:
 *     El paciente queda registrado, actualizado o inactivo; su historial de citas se conserva.
 *
 * Flujo de código: router.php -> este caso de uso -> politicas.php / repository.php -> BD.
 * Estado: PENDIENTE.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\CasosUso;

use App\Nucleo\{NoImplementado, Peticion, Sesion};

final class Cu04GestionarPacientes
{
    /** GET /api/v1/personas/pacientes — Listar y buscar pacientes (?buscar=) */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        throw new NoImplementado('CU04');
    }
    /** GET /api/v1/personas/pacientes/{id} — Datos de un paciente */
    public static function obtener(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        throw new NoImplementado('CU04');
    }
    /** POST /api/v1/personas/pacientes — Registrar paciente */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        throw new NoImplementado('CU04');
    }
    /** PUT /api/v1/personas/pacientes/{id} — Modificar paciente */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        throw new NoImplementado('CU04');
    }
    /** PATCH /api/v1/personas/pacientes/{id}/estado — Activar o desactivar paciente */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        throw new NoImplementado('CU04');
    }
}
