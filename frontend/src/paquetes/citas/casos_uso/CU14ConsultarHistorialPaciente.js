/**
 * CU14 - Consultar historial de citas del paciente  (paquete citas, Ciclo 2)
 * Actores: Recepcionista, Médico, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU14ConsultarHistorialPaciente(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU14', nombre: 'Consultar historial de citas del paciente', paquete: 'citas' })
}
