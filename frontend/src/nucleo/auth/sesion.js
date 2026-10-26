/**
 * Sesión del usuario en el navegador.
 * El backend guarda la sesión (cookie httponly); aquí solo se recuerda el usuario para pintar el menú.
 * Se completará al implementar CU01 (Iniciar y cerrar sesión).
 */
let usuarioActual = null

export function obtenerUsuario() {
  return usuarioActual
}

export function establecerUsuario(usuario) {
  usuarioActual = usuario
}

export function tienePermiso(permiso) {
  return !!usuarioActual && usuarioActual.permisos.includes(permiso)
}

// ---- CU01: inicio ----
import { sesionActual, cerrarSesion as cerrarSesionApi } from '../../paquetes/seguridad/api.js'

/** Recupera la sesión del backend (al recargar la página la memoria del navegador se pierde). */
export async function cargarSesion() {
  try {
    usuarioActual = await sesionActual()
  } catch {
    usuarioActual = null
  }
  return usuarioActual
}

/** Cierra la sesión en el servidor y en el navegador, y vuelve al inicio de sesión. */
export async function cerrarSesion() {
  try { await cerrarSesionApi() } catch { /* la sesión local se limpia igual */ }
  usuarioActual = null
  irA('/login')
}

/** Navega a una ruta; si ya es la actual, vuelve a pintarla. */
export function irA(ruta) {
  if (location.hash === '#' + ruta) window.dispatchEvent(new HashChangeEvent('hashchange'))
  else location.hash = '#' + ruta
}

// Antes de pintar la primera pantalla se espera la sesión guardada en el servidor
await cargarSesion()
// ---- CU01: fin ----
