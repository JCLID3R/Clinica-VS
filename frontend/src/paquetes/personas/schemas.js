/**
 * Validaciones de los formularios del paquete personas (en el navegador).
 * El backend vuelve a validar todo en schemas.php.
 * Estado: IMPLEMENTADO.
 */

// ---- CU05: inicio ----
export function validarRegistroProfesional(registro) {
  if (!registro) return 'El registro profesional es requerido.'
  if (!/^MP-\d{5}$/.test(registro)) {
    return 'El registro profesional debe tener formato MP-XXXXX (ej: MP-12345).'
  }
  return null
}

export function validarTelefonoOpcional(telefono) {
  if (!telefono) return null
  if (!/^\d{7,8}$/.test(telefono)) {
    return 'El teléfono debe tener 7 u 8 dígitos.'
  }
  return null
}

export function validarCorreoOpcional(correo) {
  if (!correo) return null
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
    return 'El correo no es válido.'
  }
  return null
}

export function validarEspecialidades(especialidades) {
  if (!Array.isArray(especialidades) || especialidades.length === 0) {
    return 'Debe seleccionar al menos una especialidad.'
  }
  return null
}
// ---- CU05: fin ----

export {}
