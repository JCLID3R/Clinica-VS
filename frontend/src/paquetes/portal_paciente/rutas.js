/** Rutas del paquete portal_paciente: cada ruta muestra la pantalla de su caso de uso. */
import CU16GestionarPerfilPaciente from './casos_uso/CU16GestionarPerfilPaciente.js'
import CU17ReservarMisCitas from './casos_uso/CU17ReservarMisCitas.js'

export default [
  { ruta: '/portal/perfil', codigo: 'CU16', vista: CU16GestionarPerfilPaciente },
  { ruta: '/portal/mis-citas', codigo: 'CU17', vista: CU17ReservarMisCitas },
]
