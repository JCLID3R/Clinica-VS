/**
 * CU08 - Consultar disponibilidad de citas  (paquete agenda, Ciclo 1)
 * Actores: Recepcionista, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU08ConsultarDisponibilidad(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU08', nombre: 'Consultar disponibilidad de citas', paquete: 'agenda' })
}
