/** Rutas del paquete citas: cada ruta muestra la pantalla de su caso de uso. */
import CU09ProgramarCita from './casos_uso/CU09ProgramarCita.js'
import CU10ReprogramarCita from './casos_uso/CU10ReprogramarCita.js'
import CU11CancelarCita from './casos_uso/CU11CancelarCita.js'
import CU12RegistrarAsistencia from './casos_uso/CU12RegistrarAsistencia.js'
import CU13ConsultarAgendaMedica from './casos_uso/CU13ConsultarAgendaMedica.js'
import CU14ConsultarHistorialPaciente from './casos_uso/CU14ConsultarHistorialPaciente.js'

export default [
  { ruta: '/citas/programar', codigo: 'CU09', vista: CU09ProgramarCita },
  { ruta: '/citas/reprogramar', codigo: 'CU10', vista: CU10ReprogramarCita },
  { ruta: '/citas/cancelar', codigo: 'CU11', vista: CU11CancelarCita },
  { ruta: '/citas/asistencia', codigo: 'CU12', vista: CU12RegistrarAsistencia },
  { ruta: '/citas/agenda', codigo: 'CU13', vista: CU13ConsultarAgendaMedica },
  { ruta: '/citas/historial', codigo: 'CU14', vista: CU14ConsultarHistorialPaciente },
]
