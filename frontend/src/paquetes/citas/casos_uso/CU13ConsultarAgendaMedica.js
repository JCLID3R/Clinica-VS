/**
 * CU13 - Consultar agenda médica  (paquete citas, Ciclo 2)
 * Actores: Médico, Recepcionista, Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU13ConsultarAgendaMedica(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU13', nombre: 'Consultar agenda médica', paquete: 'citas' })
}
