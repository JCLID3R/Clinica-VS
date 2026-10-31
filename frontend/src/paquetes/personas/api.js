/**
 * Llamadas a la API del paquete personas.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/personas/router.php):
 *   CU04  GET    /personas/pacientes                    Listar y buscar pacientes (?buscar=)
 *   CU04  GET    /personas/pacientes/{id}               Datos de un paciente
 *   CU04  POST   /personas/pacientes                    Registrar paciente
 *   CU04  PUT    /personas/pacientes/{id}               Modificar paciente
 *   CU04  PATCH  /personas/pacientes/{id}/estado        Activar o desactivar paciente
 *   CU05  GET    /personas/medicos                      Listar y buscar médicos
 *   CU05  POST   /personas/medicos                      Registrar médico
 *   CU05  PUT    /personas/medicos/{id}                 Modificar médico y especialidades
 *   CU05  PATCH  /personas/medicos/{id}/estado          Activar o desactivar médico
 *   CU06  GET    /personas/especialidades               Listar especialidades
 *   CU06  POST   /personas/especialidades               Registrar especialidad
 *   CU06  PUT    /personas/especialidades/{id}          Modificar especialidad
 *   CU06  PATCH  /personas/especialidades/{id}/estado   Activar o desactivar especialidad
 *
 * Estado: PENDIENTE.
 */
import { api } from '../../nucleo/api.js'

// ---- CU06: inicio ----
export const listarEspecialidades = (filtro = '', estado = '') =>
  api.get('/personas/especialidades', { filtro, estado })
export const crearEspecialidad = (datos) => api.post('/personas/especialidades', datos)
export const modificarEspecialidad = (id, datos) => api.put(`/personas/especialidades/${id}`, datos)
export const cambiarEstadoEspecialidad = (id, estado) => api.patch(`/personas/especialidades/${id}/estado`, { estado })
// ---- CU06: fin ----

export default {}
