/**
 * Llamadas a la API del paquete agenda.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/agenda/router.php):
 *   CU07  GET    /agenda/medicos/{id}/horarios          Horario semanal de un médico
 *   CU07  POST   /agenda/medicos/{id}/horarios          Agregar bloque de horario
 *   CU07  PUT    /agenda/horarios/{id}                  Modificar bloque
 *   CU07  PATCH  /agenda/horarios/{id}/estado           Activar o desactivar bloque
 *   CU08  GET    /agenda/disponibilidad                 Horarios libres (?id_medico=&fecha=)
 *
 * Estado: IMPLEMENTADO (CU07).
 */
import { api } from '../../nucleo/api.js'

// ---- CU07: Gestionar horarios de atención ----
export const listarHorariosPorMedico = (id) => api.get(`/agenda/medicos/${id}/horarios`)
export const crearHorario = (id, datos) => api.post(`/agenda/medicos/${id}/horarios`, datos)
export const modificarHorario = (id, datos) => api.put(`/agenda/horarios/${id}`, datos)
export const cambiarEstadoHorario = (id, datos) => api.patch(`/agenda/horarios/${id}/estado`, datos)
// ---- CU07: fin ----

export default {}
