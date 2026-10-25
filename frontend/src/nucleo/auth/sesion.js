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
