/**
 * CU11 - Cancelar cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU11CancelarCita(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU11', nombre: 'Cancelar cita', paquete: 'citas' })
}
