/**
 * CU17 - Reservar y dar seguimiento a mis citas  (paquete portal_paciente, Ciclo 2)
 * Actores: Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU17ReservarMisCitas(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU17', nombre: 'Reservar y dar seguimiento a mis citas', paquete: 'portal_paciente' })
}
