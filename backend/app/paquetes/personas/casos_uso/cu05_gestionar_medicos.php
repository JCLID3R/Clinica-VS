<?php
/**
 * cu05_gestionar_medicos.php
 * CU05 - Gestionar médicos
 * Paquete: personas  ·  Ciclo 1
 *
 * Registrar, listar, buscar, modificar y desactivar médicos con sus especialidades,
 * creando opcionalmente una cuenta de usuario para el médico.
 * Registro profesional único (MP-XXXXX), al menos una especialidad activa.
 * Desactivar con citas futuras Programada/Confirmada advierte y lista.
 *
 * Actores ........: Administrador
 * Permiso ........: medico.gestionar
 * Tablas .........: medico, especialidad, medico_especialidad, usuario, bitacora
 *
 * Diagrama de comunicación:
 *  1 listar(buscar, pagina, por_pagina): IU->CTR listar(criterio); CTR->Medico listarMedicos(criterio); CTR->IU mostrarMedicos(lista)
 *  2 listarEspecialidades(): IU->CTR listarEspecialidades(); CTR->Especialidad listarActivas(); CTR->IU cargarEspecialidades(lista)
 *  3 guardarMedico(datos, especialidades): IU->CTR guardar(datos); CTR->Medico existeRegistro(registroProf); CTR->Medico insertar(datos); CTR->Medico asignarEspecialidad(idMedico, idEsp); [CTR->Usuario crear(datos)] CTR->IU mensaje('Registrado')
 *  4 modificarMedico(id, datos): CTR->Medico actualizar(id, datos); CTR->Medico reemplazarEspecialidades(id, especialidades); CTR->IU mensaje('Actualizado')
 *  5 cambiarEstado(id, estado): si desactivar con citas futuras -> advierte y lista; solo si confirma -> CTR->Medico cambiarEstado(id, estado); CTR->IU mensaje('Estado actualizado')
 *
 * Estado: IMPLEMENTADO.
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\CasosUso;

use App\Nucleo\{Peticion, Sesion};
use function App\Paquetes\Personas\Repository\{especialidadListarActivas};

final class Cu05GestionarMedicos
{
    /** GET /api/v1/personas/medicos — Listar y buscar médicos */
    public static function listar(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        return Cu05Medicos::listar(
            $p->consulta('buscar') ?? '',
            $p->consulta('estado') ?? 'activo',
            (int) ($p->consulta('pagina') ?? 1),
            (int) ($p->consulta('por_pagina') ?? 20)
        );
    }

    /** POST /api/v1/personas/medicos — Registrar médico */
    public static function registrar(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        return Cu05Medicos::registrar($p);
    }

    /** PUT /api/v1/personas/medicos/{id} — Modificar médico y especialidades */
    public static function modificar(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        return Cu05Medicos::modificar($p);
    }

    /** PATCH /api/v1/personas/medicos/{id}/estado — Activar o desactivar médico */
    public static function cambiarEstado(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        return Cu05Medicos::cambiarEstado($p);
    }

    /** GET /api/v1/personas/especialidades/activas — Listar especialidades activas */
    public static function especialidadesActivas(Peticion $p): mixed
    {
        Sesion::requerir('medico.gestionar');
        return [
            'especialidades' => especialidadListarActivas(),
        ];
    }
}

// ---- CU05: inicio ----
use App\Nucleo\{BaseDatos, Correo, ErrorApi};
use function App\Paquetes\Personas\Schemas\{
    validar_listar_medicos, validar_registrar_medico, validar_modificar_medico,
    validar_cambiar_estado_medico, validar_crear_usuario_medico
};
use function App\Paquetes\Personas\Repository\{
    listarMedicos, medicoExisteRegistro, medicoInsertar, medicoAsignarEspecialidad,
    medicoModificar, medicoCambiarEstado, medicoObtenerCitasFuturas, medicoReemplazarEspecialidades,
    medicoVincularUsuario, obtenerMedicoConEspecialidades, registrarBitacora
};
use function App\Paquetes\Seguridad\Repository\{
    crearUsuario as crearUsuarioSeguridad, buscarMedicoSinCuenta, vincularMedico
};
use function App\Paquetes\Seguridad\Politicas\{generarContrasena, ipCliente};

final class Cu05Medicos
{
    public static function listar(string $buscar, string $estado, int $pagina, int $por_pagina): mixed
    {
        $buscar = $buscar ?: '';
        $estado = in_array($estado, ['activo', 'inactivo']) ? $estado : 'activo';
        $pagina = max(1, $pagina);
        $por_pagina = min(100, max(1, $por_pagina));
        return listarMedicos($buscar, $estado, $pagina, $por_pagina);
    }

    public static function registrar(Peticion $p): mixed
    {
        $datos = validar_registrar_medico($p->cuerpo());

        if (medicoExisteRegistro($datos['registro_profesional'])) {
            throw new ErrorApi(409, 'El registro profesional ya está registrado.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        $resultado = BaseDatos::transaccion(function () use ($s, $datos, $ip): array {
            $idMedico = medicoInsertar(
                $datos['registro_profesional'],
                $datos['nombres'],
                $datos['apellidos'],
                $datos['telefono'],
                $datos['correo']
            );

            foreach ($datos['especialidades'] as $idEsp) {
                medicoAsignarEspecialidad($idMedico, $idEsp);
            }

            $idUsuario = null;
            $contrasenaMostrar = null;
            if ($datos['usuario']) {
                $usuario = $datos['usuario'];
                $contrasena = $usuario['contrasena'] ?: generarContrasena();
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $rol = BaseDatos::fila(
                    'SELECT id_rol FROM rol WHERE lower(nombre) = lower(:n)',
                    [':n' => 'Medico']
                );
                if (!$rol) {
                    throw new ErrorApi(400, 'Rol Medico no encontrado.');
                }

                $existe = BaseDatos::fila(
                    'SELECT id_usuario FROM usuario WHERE lower(username) = lower(:u) OR lower(correo) = lower(:c)',
                    [':u' => $usuario['username'], ':c' => $usuario['correo']]
                );
                if ($existe) {
                    throw new ErrorApi(409, 'El usuario o correo ya está registrado.');
                }

                $idUsuario = crearUsuarioSeguridad((int) $rol['id_rol'], $usuario['username'], $usuario['correo'], $hash);
                medicoVincularUsuario($idMedico, $idUsuario);

                if ($usuario['generar_contrasena']) {
                    $contrasenaMostrar = $contrasena;
                }
            }

            registrarBitacora(
                $s['id_usuario'],
                'MEDICO_CREAR',
                $idMedico,
                "Médico registrado: {$datos['nombres']} {$datos['apellidos']} ({$datos['registro_profesional']})",
                'medico',
                $ip
            );

            return [
                'id_medico' => $idMedico,
                'nombres' => $datos['nombres'],
                'correo' => $datos['correo'],
                'id_usuario' => $idUsuario,
                'contrasena_generada' => (bool) $contrasenaMostrar,
                'contrasena' => $contrasenaMostrar,
            ];
        }, $s['id_usuario']);

        $mensaje = 'Médico registrado correctamente.';
        if ($resultado['id_usuario'] && $resultado['contrasena_generada']) {
            $mensaje .= ' Se envió la contraseña al correo.';
            $html = self::plantillaContrasena($resultado['nombres'], $resultado['contrasena']);
            Correo::enviar($resultado['correo'], 'Cuenta creada - Clínica Vida Salud', $html);
        }

        return ['mensaje' => $mensaje];
    }

    public static function modificar(Peticion $p): mixed
    {
        $id = (int) ($p->param('id') ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $medico = obtenerMedicoConEspecialidades($id);
        if (!$medico) throw new ErrorApi(404, 'Médico no encontrado.');

        $datos = validar_modificar_medico($p->cuerpo());

        if ($datos['registro_profesional'] !== $medico['registro_profesional']
            && medicoExisteRegistro($datos['registro_profesional'])) {
            throw new ErrorApi(409, 'El registro profesional ya está registrado.');
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $datos, $s, $ip, $medico) {
            medicoModificar($id, $datos['nombres'], $datos['apellidos'], $datos['telefono'], $datos['correo']);
            medicoReemplazarEspecialidades($id, $datos['especialidades']);

            registrarBitacora(
                $s['id_usuario'],
                'MEDICO_MODIFICAR',
                $id,
                "Médico actualizado: {$datos['nombres']} {$datos['apellidos']}",
                'medico',
                $ip
            );
        }, $s['id_usuario']);

        return ['mensaje' => 'Médico actualizado correctamente.'];
    }

    public static function cambiarEstado(Peticion $p): mixed
    {
        $id = (int) ($p->param('id') ?? 0);
        if (!$id) throw new ErrorApi(400, 'ID requerido.');

        $medico = BaseDatos::fila("SELECT estado FROM medico WHERE id_medico = :id", [':id' => $id]);
        if (!$medico) throw new ErrorApi(404, 'Médico no encontrado.');

        $body = $p->cuerpo();
        $datos = validar_cambiar_estado_medico($body);
        $confirmar = isset($body['confirmar']) && $body['confirmar'] === true;

        if ($datos['estado'] === 'inactivo' && !$confirmar) {
            $citas = medicoObtenerCitasFuturas($id);
            if (!empty($citas)) {
                throw new ErrorApi(409, 'Este médico tiene citas futuras programadas.', [
                    'advertencia' => true,
                    'citas' => $citas,
                ]);
            }
        }

        $s = Sesion::usuario();
        $ip = ipCliente();

        BaseDatos::transaccion(function () use ($id, $datos, $s, $ip, $medico) {
            medicoCambiarEstado($id, $datos['estado']);
            registrarBitacora(
                $s['id_usuario'],
                'MEDICO_ESTADO',
                $id,
                "Estado: {$medico['estado']} → {$datos['estado']}",
                'medico',
                $ip
            );
        }, $s['id_usuario']);

        return ['mensaje' => 'Estado actualizado correctamente.'];
    }


    private static function plantillaContrasena(string $nombre, string $contrasena): string
    {
        $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $contrasena = htmlspecialchars($contrasena, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <div style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;">
                <h2 style="color: #0F766E;">Bienvenido, $nombre</h2>
                <p>Su cuenta ha sido creada en <strong>Clínica Vida Salud</strong>.</p>
                <p><strong>Usuario:</strong> $nombre</p>
                <p><strong>Contraseña temporal:</strong> <code style="background-color: #f0f0f0; padding: 5px 10px; font-family: monospace;">$contrasena</code></p>
                <p>Por favor, cambie su contraseña en el primer inicio de sesión.</p>
            </div>
        </div>
        HTML;
    }
}
// ---- CU05: fin ----
