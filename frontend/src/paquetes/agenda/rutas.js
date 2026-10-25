/** Rutas del paquete agenda: cada ruta muestra la pantalla de su caso de uso. */
import CU07GestionarHorarios from './casos_uso/CU07GestionarHorarios.js'
import CU08ConsultarDisponibilidad from './casos_uso/CU08ConsultarDisponibilidad.js'

export default [
  { ruta: '/agenda/horarios', codigo: 'CU07', vista: CU07GestionarHorarios },
  { ruta: '/agenda/disponibilidad', codigo: 'CU08', vista: CU08ConsultarDisponibilidad },
]
