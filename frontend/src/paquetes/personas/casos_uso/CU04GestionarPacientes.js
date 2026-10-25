/**
 * CU04 - Gestionar pacientes  (paquete personas, Ciclo 1)
 * Actores: Recepcionista, Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU04GestionarPacientes(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU04', nombre: 'Gestionar pacientes', paquete: 'personas' })
}
