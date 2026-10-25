/**
 * CU15 - Generar reportes  (paquete reportes, Ciclo 2)
 * Actores: Administrador, Recepcionista
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU15GenerarReportes(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU15', nombre: 'Generar reportes', paquete: 'reportes' })
}
