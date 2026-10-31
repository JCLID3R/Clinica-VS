<?php
/**
 * cu04_gestionar_pacientes.php
 * CU04 - Gestionar pacientes
 * Paquete: personas  ·  Ciclo 1
 *
 * Registrar, listar, buscar, modificar y desactivar pacientes, consultar sus citas.
 * CI único (5-10 dígitos), nombres/apellidos/fecha obligatorios, teléfono obligatorio.
 * Contactos emergencia en la misma transacción.
 *
 * Actores ........: Recepcionista, Administrador
 * Permiso ........: paciente.gestionar
 * Tablas .........: paciente, contacto_emergencia
 *
 * Diagrama de comunicación:
 *  1 listar(buscar, pagina, por_pagina): IU->CTR buscar(criterio); CTR->Paciente buscarPorCiNombre(criterio); CTR->IU mostrarPacientes(lista)
 *  2 registrar(datos): IU->CTR guardar(datos); CTR->Paciente existeCI(ci); CTR->Paciente insertar(datos); CTR->ContactoEmergencia insertar(idPaciente, contactos); CTR->IU mensaje('Registrado')
 *  3 modificar(id, datos): CTR->Paciente actualizar(id, datos); CTR->ContactoEmergencia actualizar(idPaciente, contactos); CTR->IU mensaje('Actualizado')
 *  4 verCitas(idPaciente): CTR->Cita listarPorPaciente(idPaciente, v_agenda_medica); CTR->IU mostrarCitas(modal)
 *
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\CasosUso;

use App\Nucleo\{Peticion, Sesion};

final class Cu04GestionarPacientes
{
    /** GET /api/v1/personas/pacientes — Listar y buscar pacientes (?buscar=&pagina=&por_pagina=) */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        return Cu04Pacientes::listar($p);
    }

    /** GET /api/v1/personas/pacientes/{id} — Datos de un paciente */
    public static function obtener(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        return Cu04Pacientes::obtener($p);
    }

    /** POST /api/v1/personas/pacientes — Registrar paciente */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        return Cu04Pacientes::registrar($p);
    }

    /** PUT /api/v1/personas/pacientes/{id} — Modificar paciente */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        return Cu04Pacientes::modificar($p);
    }

    /** PATCH /api/v1/personas/pacientes/{id}/estado — Activar o desactivar paciente */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        return Cu04Pacientes::cambiarEstado($p);
    }

    /** GET /api/v1/personas/pacientes/{id}/citas — Listar citas del paciente */
    public static function citas(Peticion $p): mixed
    {
        Sesion::requerir('paciente.gestionar');
        return Cu04Pacientes::citas($p);
    }
}

// ---- CU04: inicio ----
use App\Nucleo\{BaseDatos, ErrorApi};
use function App\Paquetes\Personas\Schemas\{validar_listar_pacientes, validar_registrar_paciente, validar_modificar_paciente, validar_cambiar_estado_paciente};
use function App\Paquetes\Personas\Politicas\{ipCliente};
use function App\Paquetes\Seguridad\Repository\{registrarBitacora};

final class Cu04Pacientes
{
    public static function listar(Peticion $p): mixed
    {
        $buscar = $p->consulta('buscar') ?? '';
        $estado = $p->consulta('estado') ?? 'activo';
        $pagina = max(1, (int) ($p->consulta('pagina') ?? 1));
        $por_pagina = min(100, max(1, (int) ($p->consulta('por_pagina') ?? 20)));

        $sql = "SELECT p.id_paciente, p.ci, p.nombres, p.apellidos, p.fecha_nacimiento, p.sexo, p.telefono, p.correo, p.direccion, p.tipo_sangre, p.alergias, p.enfermedad_cronica, p.antecedentes, p.estado,
                EXTRACT(YEAR FROM age(p.fecha_nacimiento))::int as edad
        FROM paciente p
        WHERE 1=1";
        $binds = [];

        if ($estado && in_array($estado, ['activo', 'inactivo'])) {
            $sql .= " AND p.estado = :estado";
            $binds[':estado'] = $estado;
        }

        if ($buscar) {
            $buscarNorm = strtolower($buscar);
            $sql .= " AND (p.ci::text ILIKE :b OR translate(lower(p.nombres), 'áéíóúüñ', 'aeiouun') ILIKE :bn OR translate(lower(p.apellidos), 'áéíóúüñ', 'aeiouun') ILIKE :bn OR translate(lower(p.nombres || ' ' || p.apellidos), 'áéíóúüñ', 'aeiouun') ILIKE :bn)";
            $binds[':b'] = "%{$buscar}%";
            $binds[':bn'] = "%{$buscarNorm}%";
        }

        $sql .= " ORDER BY p.apellidos ASC, p.nombres ASC";

        $total = count(BaseDatos::consultar($sql, $binds));
        $offset = ($pagina - 1) * $por_pagina;

        $sql .= " LIMIT :limit OFFSET :offset";
        $binds[':limit'] = $por_pagina;
        $binds[':offset'] = $offset;

        $pacientes = BaseDatos::consultar($sql, $binds);

        return [
            'pacientes' => $pacientes,
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => (int) ceil($total / $por_pagina),
        ];
    }

    public static function obtener(Peticion $p): mixed
    {
        $id = (int) ($p->ruta['id'] ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $paciente = BaseDatos::fila(
            "SELECT * FROM paciente WHERE id_paciente = :id",
            [':id' => $id]
        );
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $contactos = BaseDatos::consultar(
            "SELECT * FROM contacto_emergencia WHERE id_paciente = :id ORDER BY id_contacto",
            [':id' => $id]
        );

        $paciente['contactos_emergencia'] = $contactos;
        return $paciente;
    }

    public static function registrar(Peticion $p): mixed
    {
        $datos = validar_registrar_paciente($p->cuerpo());

        // Verificar CI único
        if (self::existeCI($datos[':ci'])) {
            $existente = BaseDatos::fila("SELECT nombres, apellidos FROM paciente WHERE ci = :ci", [':ci' => $datos[':ci']]);
            throw new ErrorApi(409, "El CI ya está registrado a nombre de {$existente['nombres']} {$existente['apellidos']}.");
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($datos, $s, $ip) {
            $sqlDatos = array_filter($datos, fn($k) => substr((string)$k, 0, 1) === ':', ARRAY_FILTER_USE_KEY);
            $id = BaseDatos::fila(
                "INSERT INTO paciente (ci, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, tipo_sangre, alergias, enfermedad_cronica, antecedentes, estado)
                 VALUES (:ci, :nombres, :apellidos, :fecha_nacimiento, :sexo, :telefono, :correo, :direccion, :tipo_sangre, :alergias, :enfermedad_cronica, :antecedentes, 'activo')
                 RETURNING id_paciente",
                $sqlDatos
            );
            $id_paciente = (int) $id['id_paciente'];

            // Registrar contactos de emergencia
            if (!empty($datos['contactos_emergencia'])) {
                foreach ($datos['contactos_emergencia'] as $contacto) {
                    BaseDatos::ejecutar(
                        "INSERT INTO contacto_emergencia (id_paciente, nombre, parentesco, telefono) VALUES (:id_paciente, :nombre, :parentesco, :telefono)",
                        [':id_paciente' => $id_paciente, ':nombre' => $contacto['nombre'], ':parentesco' => $contacto['parentesco'], ':telefono' => $contacto['telefono']]
                    );
                }
            }

            registrarBitacora($s['id_usuario'], 'PACIENTE_CREAR', $id_paciente, "Paciente registrado: {$datos[':nombres']} {$datos[':apellidos']} CI {$datos[':ci']}", 'paciente', $ip);
        });

        return ['mensaje' => 'Paciente registrado correctamente.'];
    }

    public static function modificar(Peticion $p): mixed
    {
        $id = (int) ($p->ruta['id'] ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $datos = validar_modificar_paciente($p->cuerpo());
        $paciente = self::obtener($p);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        // Si cambia CI, verificar que el nuevo no exista
        if ((int)$datos[':ci'] !== (int)$paciente['ci'] && self::existeCI($datos[':ci'])) {
            throw new ErrorApi(409, 'El CI ya está registrado.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $datos, $s, $ip) {
            $sqlDatos = array_filter($datos, fn($k) => substr((string)$k, 0, 1) === ':', ARRAY_FILTER_USE_KEY);
            BaseDatos::ejecutar(
                "UPDATE paciente SET ci = :ci, nombres = :nombres, apellidos = :apellidos, fecha_nacimiento = :fecha_nacimiento, sexo = :sexo, telefono = :telefono, correo = :correo, direccion = :direccion, tipo_sangre = :tipo_sangre, alergias = :alergias, enfermedad_cronica = :enfermedad_cronica, antecedentes = :antecedentes WHERE id_paciente = :id",
                [...$sqlDatos, ':id' => $id]
            );

            // Actualizar contactos de emergencia
            BaseDatos::ejecutar("DELETE FROM contacto_emergencia WHERE id_paciente = :id", [':id' => $id]);
            if (!empty($datos['contactos_emergencia'])) {
                foreach ($datos['contactos_emergencia'] as $contacto) {
                    BaseDatos::ejecutar(
                        "INSERT INTO contacto_emergencia (id_paciente, nombre, parentesco, telefono) VALUES (:id_paciente, :nombre, :parentesco, :telefono)",
                        [':id_paciente' => $id, ':nombre' => $contacto['nombre'], ':parentesco' => $contacto['parentesco'], ':telefono' => $contacto['telefono']]
                    );
                }
            }

            registrarBitacora($s['id_usuario'], 'PACIENTE_MODIFICAR', $id, "Paciente actualizado: {$datos[':nombres']} {$datos[':apellidos']}", 'paciente', $ip);
        });

        return ['mensaje' => 'Paciente actualizado correctamente.'];
    }

    public static function cambiarEstado(Peticion $p): mixed
    {
        $id = (int) ($p->ruta['id'] ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $datos = validar_cambiar_estado_paciente($p->cuerpo());
        $paciente = BaseDatos::fila("SELECT estado FROM paciente WHERE id_paciente = :id", [':id' => $id]);
        if (!$paciente) throw new ErrorApi(404, 'Paciente no encontrado.');

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $datos, $s, $ip, $paciente) {
            BaseDatos::ejecutar("UPDATE paciente SET estado = :estado WHERE id_paciente = :id", [':estado' => $datos['estado'], ':id' => $id]);
            registrarBitacora($s['id_usuario'], 'PACIENTE_ESTADO', $id, "Estado: {$paciente['estado']} → {$datos['estado']}", 'paciente', $ip);
        });

        return ['mensaje' => 'Estado actualizado correctamente.'];
    }

    public static function citas(Peticion $p): mixed
    {
        $id = (int) ($p->ruta['id'] ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $citas = BaseDatos::consultar(
            "SELECT id_cita, codigo, fecha, hora_inicio, hora_fin, estado, motivo, medico, especialidad
             FROM v_agenda_medica
             WHERE id_paciente = :id
             ORDER BY fecha DESC, hora_inicio DESC",
            [':id' => $id]
        );

        return ['citas' => $citas];
    }

    private static function existeCI(string $ci): bool
    {
        $result = BaseDatos::fila("SELECT COUNT(*) as total FROM paciente WHERE ci = :ci", [':ci' => $ci]);
        return (int) $result['total'] > 0;
    }
}
// ---- CU04: fin ----
