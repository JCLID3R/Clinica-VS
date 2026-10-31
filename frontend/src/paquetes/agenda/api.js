/**
 * Llamadas a la API del paquete agenda.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/agenda/router.php):
 *   CU07  GET    /agenda/medicos/{id}/horarios          Horario semanal de un médico
 *   CU07  POST   /agenda/medicos/{id}/horarios          Agregar bloque de horario
 *   CU07  PUT    /agenda/horarios/{id}                  Modificar bloque
 *   CU07  PATCH  /agenda/horarios/{id}/estado           Activar o desactivar bloque
 *   CU08  GET    /agenda/especialidades/{id}/medicos    Médicos activos de especialidad
 *   CU08  GET    /agenda/disponibilidad                 Horarios libres (?id_especialidad=&id_medico=&fecha=)
 *
 * Estado: IMPLEMENTADO (CU07, CU08).
 */
import { api } from '../../nucleo/api.js'

// ---- CU07: Gestionar horarios de atención ----
export const listarHorariosPorMedico = (id) => api.get(`/agenda/medicos/${id}/horarios`)
export const crearHorario = (id, datos) => api.post(`/agenda/medicos/${id}/horarios`, datos)
export const modificarHorario = (id, datos) => api.put(`/agenda/horarios/${id}`, datos)
export const cambiarEstadoHorario = (id, datos) => api.patch(`/agenda/horarios/${id}/estado`, datos)
// ---- CU07: fin ----

// ---- CU08: Consultar disponibilidad ----
export const listarMedicosPorEspecialidad = (id) => api.get(`/agenda/especialidades/${id}/medicos`)
export const consultarDisponibilidad = (params) => {
  const qs = new URLSearchParams(params).toString()
  return api.get(`/agenda/disponibilidad?${qs}`)
}
// ---- CU08: fin ----

export default {}
