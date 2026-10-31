<?php
/**
 * Paquete personas — validación de los datos que entran y salen de la API.
 * Cada función recibe el cuerpo JSON de la petición y devuelve los datos limpios,
 * o lanza ErrorApi(400) con los errores por campo (usar App\Nucleo\Validador).
 *
 * Estado: PENDIENTE. Agregar aquí una función por formulario, p. ej.:
 *     function validar_<entidad>(array $datos): array { ... }
 */
declare(strict_types=1);

namespace App\Paquetes\Personas\Schemas;

use App\Nucleo\{ErrorApi, Validador};

// ---- CU04: inicio ----
function validar_listar_pacientes(array $params): array
{
    $estado = (string) ($params['estado'] ?? 'activo');
    if ($estado && !in_array($estado, ['activo', 'inactivo'])) {
        $estado = 'activo';
    }

    return [
        'buscar' => (string) ($params['buscar'] ?? ''),
        'estado' => $estado,
        'pagina' => max(1, (int) ($params['pagina'] ?? 1)),
        'por_pagina' => min(100, max(1, (int) ($params['por_pagina'] ?? 20))),
    ];
}

function validar_registrar_paciente(array $datos): array
{
    $errores = [];

    $ci = trim($datos['ci'] ?? '');
    if (!$ci) {
        $errores['ci'] = 'El CI es requerido.';
    } elseif (!ctype_digit($ci) || strlen($ci) < 5 || strlen($ci) > 10) {
        $errores['ci'] = 'El CI debe contener 5 a 10 dígitos.';
    }

    $nombres = trim($datos['nombres'] ?? '');
    if (!$nombres || strlen($nombres) < 2 || strlen($nombres) > 100) {
        $errores['nombres'] = 'Los nombres deben tener 2 a 100 caracteres.';
    }

    $apellidos = trim($datos['apellidos'] ?? '');
    if (!$apellidos || strlen($apellidos) < 2 || strlen($apellidos) > 100) {
        $errores['apellidos'] = 'Los apellidos deben tener 2 a 100 caracteres.';
    }

    $fecha_nac = trim($datos['fecha_nacimiento'] ?? '');
    if (!$fecha_nac) {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento es requerida.';
    } elseif (strtotime($fecha_nac) > time()) {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento no puede ser futura.';
    }

    $telefono = trim($datos['telefono'] ?? '');
    if (!$telefono) {
        $errores['telefono'] = 'El teléfono es requerido.';
    } elseif (!preg_match('/^\d{7,8}$/', $telefono)) {
        $errores['telefono'] = 'El teléfono debe tener 7 u 8 dígitos.';
    }

    $correo = trim($datos['correo'] ?? '');
    if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'El correo no es válido.';
    }

    $sexo = trim($datos['sexo'] ?? '');
    if ($sexo && !in_array($sexo, ['M', 'F', 'O'])) {
        $errores['sexo'] = 'El sexo debe ser M, F u O.';
    }

    $tipo_sangre = trim($datos['tipo_sangre'] ?? '');
    if ($tipo_sangre && !in_array($tipo_sangre, ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'])) {
        $errores['tipo_sangre'] = 'El tipo de sangre no es válido.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida.', $errores);
    }

    return [
        ':ci' => $ci,
        ':nombres' => $nombres,
        ':apellidos' => $apellidos,
        ':fecha_nacimiento' => $fecha_nac,
        ':sexo' => $sexo ?: null,
        ':telefono' => $telefono,
        ':correo' => $correo ?: null,
        ':direccion' => trim($datos['direccion'] ?? '') ?: null,
        ':tipo_sangre' => $tipo_sangre ?: null,
        ':alergias' => trim($datos['alergias'] ?? '') ?: null,
        ':enfermedad_cronica' => trim($datos['enfermedad_cronica'] ?? '') ?: null,
        ':antecedentes' => trim($datos['antecedentes'] ?? '') ?: null,
        'contactos_emergencia' => $datos['contactos_emergencia'] ?? [],
    ];
}

function validar_modificar_paciente(array $datos): array
{
    return validar_registrar_paciente($datos);
}

function validar_cambiar_estado_paciente(array $datos): array
{
    $estado = strtolower(trim($datos['estado'] ?? ''));
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        throw new ErrorApi(400, 'Estado inválido. Debe ser "activo" o "inactivo".', ['estado' => $estado]);
    }

    return ['estado' => $estado];
}
// ---- CU04: fin ----

// ---- CU06: inicio ----
function validar_listar_especialidades(array $params): array
{
    return [
        'filtro' => (string) ($params['filtro'] ?? ''),
        'estado' => (string) ($params['estado'] ?? ''),
        'pagina' => max(1, (int) ($params['pagina'] ?? 1)),
        'por_pagina' => min(100, max(1, (int) ($params['por_pagina'] ?? 20))),
    ];
}

function validar_registrar_especialidad(array $datos): array
{
    $errores = [];
    $v = new Validador($datos);

    $nombre = trim($datos['nombre'] ?? '');
    if (!$nombre) {
        $errores['nombre'] = 'El nombre es requerido.';
    } elseif (strlen($nombre) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (strlen($nombre) > 60) {
        $errores['nombre'] = 'El nombre no debe exceder 60 caracteres.';
    }

    $descripcion = trim($datos['descripcion'] ?? '');
    if ($descripcion && strlen($descripcion) > 200) {
        $errores['descripcion'] = 'La descripción no debe exceder 200 caracteres.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida.', $errores);
    }

    return [
        'nombre' => $nombre,
        'descripcion' => $descripcion ?: null,
    ];
}

function validar_modificar_especialidad(array $datos): array
{
    $errores = [];

    $nombre = trim($datos['nombre'] ?? '');
    if (!$nombre) {
        $errores['nombre'] = 'El nombre es requerido.';
    } elseif (strlen($nombre) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (strlen($nombre) > 60) {
        $errores['nombre'] = 'El nombre no debe exceder 60 caracteres.';
    }

    $descripcion = trim($datos['descripcion'] ?? '');
    if ($descripcion && strlen($descripcion) > 200) {
        $errores['descripcion'] = 'La descripción no debe exceder 200 caracteres.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida.', $errores);
    }

    return [
        'nombre' => $nombre,
        'descripcion' => $descripcion ?: null,
    ];
}

function validar_cambiar_estado_especialidad(array $datos): array
{
    $estado = strtolower(trim($datos['estado'] ?? ''));
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        throw new ErrorApi(400, 'Estado inválido. Debe ser "activo" o "inactivo".', ['estado' => $estado]);
    }

    return ['estado' => $estado];
}
// ---- CU06: fin ----

// ---- CU05: inicio ----
function validar_listar_medicos(array $params): array
{
    $estado = (string) ($params['estado'] ?? 'activo');
    if ($estado && !in_array($estado, ['activo', 'inactivo'])) {
        $estado = 'activo';
    }

    return [
        'buscar' => (string) ($params['buscar'] ?? ''),
        'estado' => $estado,
        'pagina' => max(1, (int) ($params['pagina'] ?? 1)),
        'por_pagina' => min(100, max(1, (int) ($params['por_pagina'] ?? 20))),
    ];
}

function validar_registrar_medico(array $datos): array
{
    $errores = [];

    $registro = trim($datos['registro_profesional'] ?? '');
    if (!$registro) {
        $errores['registro_profesional'] = 'El registro profesional es requerido.';
    } elseif (!preg_match('/^MP-\d{5}$/', $registro)) {
        $errores['registro_profesional'] = 'El registro profesional debe tener formato MP-XXXXX.';
    }

    $nombres = trim($datos['nombres'] ?? '');
    if (!$nombres || strlen($nombres) < 2 || strlen($nombres) > 100) {
        $errores['nombres'] = 'Los nombres deben tener 2 a 100 caracteres.';
    }

    $apellidos = trim($datos['apellidos'] ?? '');
    if (!$apellidos || strlen($apellidos) < 2 || strlen($apellidos) > 100) {
        $errores['apellidos'] = 'Los apellidos deben tener 2 a 100 caracteres.';
    }

    $telefono = trim($datos['telefono'] ?? '');
    if ($telefono && !preg_match('/^\d{7,8}$/', $telefono)) {
        $errores['telefono'] = 'El teléfono debe tener 7 u 8 dígitos.';
    }

    $correo = trim($datos['correo'] ?? '');
    if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'El correo no es válido.';
    }

    $especialidades = $datos['especialidades'] ?? [];
    if (!is_array($especialidades) || empty($especialidades)) {
        $errores['especialidades'] = 'Debe seleccionar al menos una especialidad.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida.', $errores);
    }

    $usuario = null;
    if (isset($datos['crear_usuario']) && $datos['crear_usuario']) {
        $usuario = validar_crear_usuario_medico($datos['usuario'] ?? []);
    }

    return [
        'registro_profesional' => $registro,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'telefono' => $telefono ?: null,
        'correo' => $correo ?: null,
        'especialidades' => array_map('intval', $especialidades),
        'usuario' => $usuario,
    ];
}

function validar_crear_usuario_medico(array $datos): ?array
{
    if (empty($datos)) {
        return null;
    }

    $errores = [];

    $username = trim($datos['username'] ?? '');
    if (!$username || strlen($username) < 3 || strlen($username) > 40) {
        $errores['username'] = 'El usuario debe tener 3 a 40 caracteres.';
    }

    $correo = trim($datos['correo'] ?? '');
    if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'El correo es requerido y debe ser válido.';
    }

    $contrasena = trim($datos['contrasena'] ?? '');
    $generar = $datos['generar_contrasena'] ?? false;
    if (!$generar && !$contrasena) {
        $errores['contrasena'] = 'Proporcione una contraseña o genere una.';
    }

    if ($errores) {
        throw new ErrorApi(400, 'Validación fallida en usuario.', $errores);
    }

    return [
        'username' => $username,
        'correo' => $correo,
        'contrasena' => $contrasena ?: null,
        'generar_contrasena' => (bool) $generar,
    ];
}

function validar_modificar_medico(array $datos): array
{
    return validar_registrar_medico($datos);
}

function validar_cambiar_estado_medico(array $datos): array
{
    $estado = strtolower(trim($datos['estado'] ?? ''));
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        throw new ErrorApi(400, 'Estado inválido. Debe ser "activo" o "inactivo".', ['estado' => $estado]);
    }

    return ['estado' => $estado];
}
// ---- CU05: fin ----
