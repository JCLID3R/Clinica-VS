/**
 * CU06 - Gestionar especialidades  (paquete personas, Ciclo 1)
 * Actores: Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU06GestionarEspecialidades(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU06', nombre: 'Gestionar especialidades', paquete: 'personas' })
}
