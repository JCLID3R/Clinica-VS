/**
 * CU12 - Registrar asistencia de la cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista, Médico
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU12RegistrarAsistencia(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU12', nombre: 'Registrar asistencia de la cita', paquete: 'citas' })
}
