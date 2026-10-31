/** Página de inicio: resumen de paquetes y estado de la API. */
import { PAQUETES } from './mapaCasosUso.js'
import { api } from './api.js'
import { el } from '../componentes/comunes.js'
import { obtenerUsuario } from './auth/sesion.js'
import { icono } from '../componentes/iconos.js'

// ---- REDISENO: inicio ----
export default function PaginaInicio(contenedor) {
  const usuario = obtenerUsuario()
  const estado = el('p', { class: 'estado' }, 'Comprobando conexión con la API…')

  if (!usuario) {
    contenedor.append(
      el('h1', {}, 'Clínica Vida Salud'),
      el('p', {}, 'Sistema web de gestión de citas médicas.'),
      estado,
    )
  } else {
    // Bienvenida personalizada según la hora
    const ahora = new Date()
    const hora = ahora.getHours()
    let saludo = 'Buenos días'
    if (hora >= 12 && hora < 18) saludo = 'Buenas tardes'
    else if (hora >= 18) saludo = 'Buenas noches'

    const btnProgramar = el('a', { href: '#/citas/programar', class: 'btn' })
    btnProgramar.append(icono.programarCita(18), 'Programar cita')

    contenedor.append(
      el('div', { class: 'encabezado-pagina' }, [
        el('h1', {}, `${saludo}, ${usuario.username}`),
        usuario.rol === 'Administrador' ? btnProgramar : null,
      ]),
      el('p', { class: 'subtitulo' }, 'Bienvenido al sistema de gestión de citas médicas'),
      estado,
    )
  }

  api.get('/salud/bd')
    .then((r) => {
      estado.replaceChildren(icono.asistencia(16), ' API conectada · base de datos ', r.base)
      estado.classList.add('ok')
    })
    .catch((e) => {
      estado.replaceChildren(icono.cerrar(16), ' Sin conexión: ', e.message)
      estado.classList.add('error')
    })
}
// ---- REDISENO: fin ----
