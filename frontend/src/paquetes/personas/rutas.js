/** Rutas del paquete personas: cada ruta muestra la pantalla de su caso de uso. */
import CU04GestionarPacientes from './casos_uso/CU04GestionarPacientes.js'
import CU05GestionarMedicos from './casos_uso/CU05GestionarMedicos.js'
import CU06GestionarEspecialidades from './casos_uso/CU06GestionarEspecialidades.js'

export default [
  { ruta: '/personas/pacientes', codigo: 'CU04', vista: CU04GestionarPacientes },
  { ruta: '/personas/medicos', codigo: 'CU05', vista: CU05GestionarMedicos },
  { ruta: '/personas/especialidades', codigo: 'CU06', vista: CU06GestionarEspecialidades },
]
