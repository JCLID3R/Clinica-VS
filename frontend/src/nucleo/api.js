/**
 * Cliente HTTP del frontend. Todas las pantallas hablan con el backend solo a través de este archivo.
 * - Envía y recibe JSON.
 * - Envía la cookie de sesión (credentials: 'include').
 * - Si el backend responde con error, lanza ErrorApi con el mensaje y los errores por campo.
 */
import { API_URL } from './config.js'

export class ErrorApi extends Error {
  constructor(estado, mensaje, detalle = {}) {
    super(mensaje)
    this.estado = estado
    this.detalle = detalle
  }
}

async function solicitar(metodo, ruta, cuerpo) {
  const resp = await fetch(API_URL + ruta, {
    method: metodo,
    credentials: 'include',
    headers: cuerpo !== undefined ? { 'Content-Type': 'application/json' } : {},
    body: cuerpo !== undefined ? JSON.stringify(cuerpo) : undefined,
  })
  if (resp.status === 204) return null
  const datos = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const e = datos.error || {}
    throw new ErrorApi(resp.status, e.mensaje || 'Error de comunicación con el servidor.', e.detalle || {})
  }
  return datos
}

export const api = {
  get: (ruta) => solicitar('GET', ruta),
  post: (ruta, cuerpo) => solicitar('POST', ruta, cuerpo),
  put: (ruta, cuerpo) => solicitar('PUT', ruta, cuerpo),
  patch: (ruta, cuerpo) => solicitar('PATCH', ruta, cuerpo),
  del: (ruta) => solicitar('DELETE', ruta),
}
