/**
 * Llamadas a la API del paquete seguridad.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/seguridad/router.php):
 *   CU01  POST   /seguridad/sesion                      Iniciar sesión
 *   CU01  DELETE /seguridad/sesion                      Cerrar sesión
 *   CU01  GET    /seguridad/sesion                      Usuario de la sesión actual
 *   CU02  GET    /seguridad/usuarios                    Listar usuarios
 *   CU02  POST   /seguridad/usuarios                    Registrar usuario
 *   CU02  PUT    /seguridad/usuarios/{id}               Modificar usuario
 *   CU02  PATCH  /seguridad/usuarios/{id}/estado        Activar o desactivar usuario
 *   CU03  GET    /seguridad/roles                       Listar roles
 *   CU03  GET    /seguridad/permisos                    Listar permisos
 *   CU03  GET    /seguridad/roles/{id}/permisos         Permisos de un rol
 *   CU03  PUT    /seguridad/roles/{id}/permisos         Asignar permisos a un rol
 *
 * Estado: PENDIENTE.
 */
import { api } from '../../nucleo/api.js'

export default {}

// ---- CU01: inicio ----
export const iniciarSesion = (login, contrasena) => api.post('/seguridad/sesion', { login, contrasena })
export const cerrarSesion = () => api.del('/seguridad/sesion')
export const sesionActual = () => api.get('/seguridad/sesion')
export const recuperarContrasena = (correo) => api.post('/seguridad/recuperar', { correo })
export const cambiarContrasena = (datos) => api.put('/seguridad/contrasena', datos)
// ---- CU01: fin ----

// ---- CU02: inicio ----
export const listarUsuarios = (filtro = '', rol = '', estado = '', pagina = 1, por_pagina = 25) => {
  const params = new URLSearchParams()
  if (filtro) params.append('filtro', filtro)
  if (rol) params.append('rol', rol)
  if (estado) params.append('estado', estado)
  params.append('pagina', pagina)
  params.append('por_pagina', por_pagina)
  return api.get(`/seguridad/usuarios?${params.toString()}`)
}
export const crearUsuario = (datos) => api.post('/seguridad/usuarios', datos)
export const modificarUsuario = (id, datos) => api.put(`/seguridad/usuarios/${id}`, datos)
export const cambiarEstadoUsuario = (id, estado) => api.patch(`/seguridad/usuarios/${id}/estado`, { estado })
// ---- CU02: fin ----

// ---- CU03: inicio ----
export const listarRoles = () => api.get('/seguridad/roles')
export const listarPermisos = () => api.get('/seguridad/permisos')
export const obtenerPermisosRol = (id) => api.get(`/seguridad/roles/${id}/permisos`)
export const asignarPermisos = (id, permisos) => api.put(`/seguridad/roles/${id}/permisos`, { permisos })
// ---- CU03: fin ----

// ---- BITACORA: inicio ----
import { API_URL } from '../../nucleo/config.js'

export const listarBitacora = (filtros) => {
  const params = new URLSearchParams()
  if (filtros.usuario) params.append('usuario', filtros.usuario)
  if (filtros.tabla) params.append('tabla', filtros.tabla)
  if (filtros.accion) params.append('accion', filtros.accion)
  if (filtros.desde) params.append('desde', filtros.desde)
  if (filtros.hasta) params.append('hasta', filtros.hasta)
  if (filtros.pagina) params.append('pagina', filtros.pagina)
  if (filtros.por_pagina) params.append('por_pagina', filtros.por_pagina)
  return api.get(`/seguridad/bitacora?${params.toString()}`)
}
export const exportarBitacoraCsv = (filtros) => {
  const params = new URLSearchParams()
  if (filtros.usuario) params.append('usuario', filtros.usuario)
  if (filtros.tabla) params.append('tabla', filtros.tabla)
  if (filtros.accion) params.append('accion', filtros.accion)
  if (filtros.desde) params.append('desde', filtros.desde)
  if (filtros.hasta) params.append('hasta', filtros.hasta)
  window.location.href = `${API_URL}/seguridad/bitacora/csv?${params.toString()}`
}
export const resumenBitacora = () => api.get('/seguridad/bitacora/resumen')
// ---- BITACORA: fin ----
