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
 *   CU05  GET    /personas/especialidades/activas       Listar especialidades activas
 *   CU06  GET    /personas/especialidades               Listar especialidades
 *   CU06  POST   /personas/especialidades               Registrar especialidad
 *   CU06  PUT    /personas/especialidades/{id}          Modificar especialidad
 *   CU06  PATCH  /personas/especialidades/{id}/estado   Activar o desactivar especialidad
 *
 * Estado: IMPLEMENTADO.
 */
import { api } from '../../nucleo/api.js'

// ---- CU04: inicio ----
export const listarPacientes = (query = '') => api.get(`/personas/pacientes${query}`)
export const buscarPaciente = (buscar) => api.get(`/personas/pacientes?buscar=${encodeURIComponent(buscar)}`)
export const crearPaciente = (datos) => api.post('/personas/pacientes', datos)
export const modificarPaciente = (id, datos) => api.put(`/personas/pacientes/${id}`, datos)
export const cambiarEstadoPaciente = (id, estado) => api.patch(`/personas/pacientes/${id}/estado`, { estado })
// ---- CU04: fin ----

// ---- CU05: inicio ----
export const listarMedicos = (query = '') => api.get(`/personas/medicos${query}`)
export const crearMedico = (datos) => api.post('/personas/medicos', datos)
export const modificarMedico = (id, datos) => api.put(`/personas/medicos/${id}`, datos)
export const cambiarEstadoMedico = (id, datos) => api.patch(`/personas/medicos/${id}/estado`, datos)
export const listarEspecialidadesActivas = () => api.get('/personas/especialidades/activas')
// ---- CU05: fin ----

// ---- CU06: inicio ----
export const listarEspecialidades = (pagina = 1, por_pagina = 25, filtro = '', estado = '') => {
  const params = new URLSearchParams()
  if (filtro) params.append('filtro', filtro)
  if (estado) params.append('estado', estado)
  params.append('pagina', pagina)
  params.append('por_pagina', por_pagina)
  return api.get(`/personas/especialidades?${params.toString()}`)
}
export const crearEspecialidad = (datos) => api.post('/personas/especialidades', datos)
export const modificarEspecialidad = (id, datos) => api.put(`/personas/especialidades/${id}`, datos)
export const cambiarEstadoEspecialidad = (id, estado) => api.patch(`/personas/especialidades/${id}/estado`, { estado })
// ---- CU06: fin ----

export default {}
