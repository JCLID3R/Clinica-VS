/**
 * Protege las rutas internas: si no hay sesión, redirige a #/login.
 * Mientras CU01 esté pendiente, todas las rutas se pueden ver (MODO_DESARROLLO = true).
 */
import { obtenerUsuario } from './sesion.js'

const MODO_DESARROLLO = true

export function puedeEntrar() {
  return MODO_DESARROLLO || obtenerUsuario() !== null
}
