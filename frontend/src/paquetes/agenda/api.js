/**
 * Llamadas a la API del paquete agenda.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/agenda/router.php):
 *   CU07  GET    /agenda/medicos/{id}/horarios          Horario semanal de un médico
 *   CU07  POST   /agenda/medicos/{id}/horarios          Agregar bloque de horario
 *   CU07  PUT    /agenda/horarios/{id}                  Modificar bloque
 *   CU07  PATCH  /agenda/horarios/{id}/estado           Activar o desactivar bloque
 *   CU07  GET    /agenda/bloqueos                       Fechas bloqueadas
 *   CU07  POST   /agenda/bloqueos                       Registrar fecha bloqueada
 *   CU08  GET    /agenda/especialidades/{id}/medicos    Médicos activos de una especialidad
 *   CU08  GET    /agenda/disponibilidad                 Horarios libres (?id_medico=&fecha=)
 *
 * Estado: PENDIENTE.
 */
import { api } from '../../nucleo/api.js'

export default {}
