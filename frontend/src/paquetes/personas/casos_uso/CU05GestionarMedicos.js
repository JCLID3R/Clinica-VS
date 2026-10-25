/**
 * CU05 - Gestionar médicos  (paquete personas, Ciclo 1)
 * Actores: Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU05GestionarMedicos(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU05', nombre: 'Gestionar médicos', paquete: 'personas' })
}
