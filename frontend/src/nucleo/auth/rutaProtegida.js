/**
 * Protege las rutas internas: si no hay sesión, redirige a #/login.
 * Mientras CU01 esté pendiente, todas las rutas se pueden ver (MODO_DESARROLLO = true).
 */
import { obtenerUsuario } from './sesion.js'

const MODO_DESARROLLO = false

export function puedeEntrar() {
  return MODO_DESARROLLO || sesionValidaCU01()
}

// ---- CU01: inicio ----
/** Hay sesión y no queda pendiente el cambio obligatorio de contraseña (si queda, se muestra en #/login). */
function sesionValidaCU01() {
  const usuario = obtenerUsuario()
  return usuario !== null && !usuario.debe_cambiar_contrasena
}
// ---- CU01: fin ----
