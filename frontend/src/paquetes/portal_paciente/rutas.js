import CU16RegistroPaciente from './casos_uso/CU16RegistroPaciente.js'
import CU16GestionarPerfilPaciente from './casos_uso/CU16GestionarPerfilPaciente.js'
import CU17ProgramarCita from './casos_uso/CU17ProgramarCita.js'
import CU17MisCitas from './casos_uso/CU17MisCitas.js'

export default [
  { ruta: '/registro', codigo: 'CU16', vista: CU16RegistroPaciente },
  { ruta: '/portal/perfil', codigo: 'CU16', vista: CU16GestionarPerfilPaciente },
  { ruta: '/portal/programar-cita', codigo: 'CU17', vista: CU17ProgramarCita },
  { ruta: '/portal/mis-citas', codigo: 'CU17', vista: CU17MisCitas },
]
