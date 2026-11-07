/**
 * Llamadas a la API del paquete reportes.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/reportes/router.php):
 *   CU15  GET    /reportes/{tipo}                       Generar reporte (?desde=&hasta=&id_medico=&id_especialidad=&estado=)
 *   CU15  GET    /reportes/{tipo}/csv                   Exportar reporte a CSV
 *
 * Estado: IMPLEMENTADO.
 */
import { api } from '../../nucleo/api.js'

// ---- CU15: Generar reportes ----
export const generarReporte = (tipo, query) => api.get(`/reportes/${tipo}${query}`)
export const descargarCsv = (tipo, query) => {
  const url = `/api/v1/reportes/${tipo}/csv${query}`
  window.location.href = url
}
// ---- CU15: fin ----

export default {}
