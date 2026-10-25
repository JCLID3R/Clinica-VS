/**
 * CU01 - Iniciar y cerrar sesión  (paquete seguridad, Ciclo 1)
 * Actores: Administrador, Recepcionista, Médico, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU01IniciarSesion(contenedor) {
  CasoUsoPendiente(contenedor, { codigo: 'CU01', nombre: 'Iniciar y cerrar sesión', paquete: 'seguridad' })
}
