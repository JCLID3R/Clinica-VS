/**
 * CU09 - Programar cita médica  (paquete citas, Ciclo 1)
 * Actores: Recepcionista
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU09ProgramarCita(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU09', nombre: 'Programar cita médica', paquete: 'citas' })
}
