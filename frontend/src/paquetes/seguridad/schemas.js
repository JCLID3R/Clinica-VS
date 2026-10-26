/**
 * Validaciones de los formularios del paquete seguridad (en el navegador).
 * El backend vuelve a validar todo en schemas.php.
 * Estado: PENDIENTE.
 */
export {}

// ---- CU01: inicio ----
/**
 * Contraseña segura (misma regla que validarContrasena() de backend/.../seguridad/politicas.php).
 * La reutilizan CU02 y CU16. Cada requisito sirve también para la lista que se marca en verde.
 */
export const REQUISITOS_CONTRASENA = [
  { texto: 'Al menos 8 caracteres', falta: 'al menos 8 caracteres', cumple: (c) => [...c].length >= 8 },
  { texto: 'Una letra mayúscula', falta: 'una letra mayúscula', cumple: (c) => /\p{Lu}/u.test(c) },
  { texto: 'Una letra minúscula', falta: 'una letra minúscula', cumple: (c) => /\p{Ll}/u.test(c) },
  { texto: 'Un número', falta: 'un número', cumple: (c) => /\d/.test(c) },
  { texto: 'Un carácter especial (por ejemplo ! @ # $ * ?)', falta: 'un carácter especial', cumple: (c) => /[^\p{L}\d\s]/u.test(c) },
]

/** Devuelve la lista de requisitos que faltan (vacía si la contraseña cumple). */
export function validarContrasena(contrasena) {
  return REQUISITOS_CONTRASENA.filter((r) => !r.cumple(contrasena || '')).map((r) => r.falta)
}

export function mensajeContrasena(faltan) {
  return `Debe tener: ${faltan.join(', ')}.`
}

const CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

/** Formulario de inicio de sesión. Devuelve { campo: mensaje } (vacío si es válido). */
export function validarLogin({ login, contrasena }) {
  const errores = {}
  if (!login.trim()) errores.login = 'Ingrese su usuario o correo.'
  else if (login.trim().length > 100) errores.login = 'Máximo 100 caracteres.'
  if (!contrasena) errores.contrasena = 'Ingrese su contraseña.'
  else {
    const faltan = validarContrasena(contrasena)
    if (faltan.length) errores.contrasena = `La contraseña no tiene el formato válido. ${mensajeContrasena(faltan)}`
  }
  return errores
}

export function validarRecuperar({ correo }) {
  const errores = {}
  if (!correo.trim()) errores.correo = 'Ingrese su correo electrónico.'
  else if (!CORREO.test(correo.trim()) || correo.trim().length > 100) errores.correo = 'Correo electrónico inválido.'
  return errores
}

export function validarCambioContrasena({ actual, nueva, confirmacion }) {
  const errores = {}
  if (!actual) errores.actual = 'Ingrese su contraseña actual.'
  const faltan = validarContrasena(nueva)
  if (!nueva) errores.nueva = 'Ingrese la nueva contraseña.'
  else if (faltan.length) errores.nueva = mensajeContrasena(faltan)
  else if (nueva === actual) errores.nueva = 'La nueva contraseña debe ser distinta de la actual.'
  if (!confirmacion) errores.confirmacion = 'Repita la nueva contraseña.'
  else if (confirmacion !== nueva) errores.confirmacion = 'La confirmación no coincide con la nueva contraseña.'
  return errores
}
// ---- CU01: fin ----
