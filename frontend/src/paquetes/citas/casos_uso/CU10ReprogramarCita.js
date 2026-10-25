/**
 * CU10 - Reprogramar cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU10ReprogramarCita(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU10', nombre: 'Reprogramar cita', paquete: 'citas' })
}
