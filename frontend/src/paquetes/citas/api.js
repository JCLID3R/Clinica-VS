/**
 * Llamadas a la API del paquete citas.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/citas/router.php):
 *   CU09  POST   /citas                                 Programar cita
 *   CU10  PATCH  /citas/{id}/reprogramar                Reprogramar cita
 *   CU11  PATCH  /citas/{id}/cancelar                   Cancelar cita con motivo
 *   CU12  GET    /citas/{id}/acciones                   Acciones válidas según el estado
 *   CU12  PATCH  /citas/{id}/estado                     Confirmar, atender o registrar inasistencia
 *   CU13  GET    /citas/agenda                          Agenda médica (?id_medico=&fecha=&estado=)
 *   CU14  GET    /citas/pacientes/{id}/historial        Historial de citas de un paciente
 *   CU14  GET    /citas/{id}/cambios                    Cambios de estado de una cita
 *
 * Estado: IMPLEMENTADO (CU09).
 */
import { api } from '../../nucleo/api.js'

// ---- CU09: Programar cita ----
export const programarCita = (datos) => api.post('/citas', datos)
// ---- CU09: fin ----

// ---- CU10: Reprogramar cita ----
export const buscarCitas = (query) => api.get(`/citas${query}`)
export const reprogramarCita = (id, datos) => api.patch(`/citas/${id}/reprogramar`, datos)
// ---- CU10: fin ----

// ---- CU11: Cancelar cita ----
export const cancelarCita = (id, datos) => api.patch(`/citas/${id}/cancelar`, datos)
// ---- CU11: fin ----

// ---- CU12: Registrar asistencia ----
export const citasAsistencia = (query) => api.get(`/citas/asistencia${query}`)
export const accionesValidas = (id) => api.get(`/citas/${id}/acciones`)
export const cambiarEstadoCita = (id, datos) => api.patch(`/citas/${id}/estado`, datos)
export const obtenerInasistenciasPendientes = () => api.get('/citas/inasistencias-pendientes')
export const marcarInasistencias = (datos) => api.post('/citas/inasistencias-pendientes', datos)
// ---- CU12: fin ----

export default {}
