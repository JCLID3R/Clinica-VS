/**
 * Llamadas a la API del paquete portal_paciente.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/portal_paciente/router.php):
 *   CU16  POST   /portal/registro                         Crear cuenta del portal (autorregistro)
 *   CU16  GET    /portal/perfil                           Mi perfil
 *   CU16  PUT    /portal/perfil                           Actualizar mi perfil
 *   CU16  POST   /portal/cuentas/{id}                     Crear cuenta de paciente desde CU04
 *   CU17  GET    /portal/especialidades                   Especialidades activas
 *   CU17  GET    /portal/especialidades/{id}/medicos      Médicos de especialidad
 *   CU17  GET    /portal/disponibilidad                   Fichas disponibles
 *   CU17  GET    /portal/citas                            Mis citas por estado
 *   CU17  POST   /portal/citas                            Reservar cita en línea
 *   CU17  PATCH  /portal/citas/{id}/cancelar              Cancelar mi cita
 *   CU17  PATCH  /portal/citas/{id}/reprogramar          Reprogramar mi cita
 *
 * Estado: PENDIENTE.
 */
import { api } from '../../nucleo/api.js'

export const registrarCuenta = (ci, fechaNacimiento, login, contrasena) =>
  api.post('/portal/registro', { ci, fecha_nacimiento: fechaNacimiento, login, contrasena })

export const obtenerPerfil = () => api.get('/portal/perfil')
export const actualizarPerfil = (datos) => api.put('/portal/perfil', datos)

export const crearCuentaPaciente = (idPaciente) => api.post(`/portal/cuentas/${idPaciente}`, {})

export const listarEspecialidades = () => api.get('/portal/especialidades')
export const medicosPorEspecialidad = (idEspecialidad) => api.get(`/portal/especialidades/${idEspecialidad}/medicos`)
export const horariosDisponibles = (idEspecialidad, idMedico, fecha) => {
  const params = new URLSearchParams()
  params.append('id_especialidad', idEspecialidad)
  if (idMedico) params.append('id_medico', idMedico)
  params.append('fecha', fecha)
  return api.get(`/portal/disponibilidad?${params.toString()}`)
}

export const misCitas = () => api.get('/portal/citas')
export const reservarCita = (idEspecialidad, idMedico, fecha, horaInicio, horaFin) =>
  api.post('/portal/citas', { id_especialidad: idEspecialidad, id_medico: idMedico, fecha, hora_inicio: horaInicio, hora_fin: horaFin })

export const cancelarCita = (idCita, motivo) => api.patch(`/portal/citas/${idCita}/cancelar`, { motivo_cancelacion: motivo })
export const reprogramarCita = (idCita, fecha, horaInicio, horaFin) =>
  api.patch(`/portal/citas/${idCita}/reprogramar`, { fecha, hora_inicio: horaInicio, hora_fin: horaFin })

export default {}
