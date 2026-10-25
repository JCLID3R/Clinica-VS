/**
 * CU16 - Gestionar perfil del paciente  (paquete portal_paciente, Ciclo 2)
 * Actores: Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU16GestionarPerfilPaciente(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU16', nombre: 'Gestionar perfil del paciente', paquete: 'portal_paciente' })
}
