/**
 * CU07 - Gestionar horarios de atención  (paquete agenda, Ciclo 1)
 * Actores: Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU07GestionarHorarios(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU07', nombre: 'Gestionar horarios de atención', paquete: 'agenda' })
}
