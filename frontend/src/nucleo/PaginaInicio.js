/** Panel de inicio: resumen de indicadores y estado. */
import { api } from './api.js'
import { el } from '../componentes/comunes.js'
import { icono } from '../componentes/iconos.js'
import { obtenerUsuario } from './auth/sesion.js'

export default function PaginaInicio(contenedor) {
  const usuario = obtenerUsuario()

  if (!usuario) {
    contenedor.replaceChildren(
      el('h1', {}, 'Clínica Vida Salud'),
      el('p', {}, 'Sistema web de gestión de citas médicas.')
    )
    return
  }

  const ahora = new Date()
  const hora = ahora.getHours()
  let saludo = 'Buenos días'
  if (hora >= 12 && hora < 18) saludo = 'Buenas tardes'
  else if (hora >= 18) saludo = 'Buenas noches'

  const panelIndicadores = crearPanelIndicadores()
  const panelCitasHoy = el('div', { id: 'citas-hoy' })
  const panelBitacora = el('div', { id: 'bitacora' })

  contenedor.replaceChildren(
    el('div', { class: 'panel' }, [
      el('h1', {}, `${saludo}, ${usuario.username}`),
      el('p', { class: 'subtitulo' }, 'Resumen del sistema'),
      panelIndicadores,
      el('h3', {}, 'Citas de hoy'),
      panelCitasHoy,
      el('h3', {}, 'Actividad reciente'),
      panelBitacora,
    ])
  )

  cargarIndicadores(panelIndicadores)
  cargarCitasHoy(panelCitasHoy)
  cargarBitacora(panelBitacora)
}

function crearPanelIndicadores() {
  const grid = el('div', { class: 'tarjetas' })

  const tarjetas = [
    { id: 'hoy', titulo: 'Citas hoy', numero: '-', icoFn: 'calendario' },
    { id: 'programadas', titulo: 'Programadas', numero: '-', icoFn: 'programarCita' },
    { id: 'atendidas', titulo: 'Atendidas', numero: '-', icoFn: 'asistencia' },
    { id: 'canceladas', titulo: 'Canceladas', numero: '-', icoFn: 'cerrar' },
    { id: 'pacientes', titulo: 'Pacientes', numero: '-', icoFn: 'pacientes' },
    { id: 'medicos', titulo: 'Médicos', numero: '-', icoFn: 'medicos' },
  ]

  tarjetas.forEach((t) => {
    const tarjeta = el('div', { class: 'tarjeta estadistica', id: `tarjeta-${t.id}` })
    const iconDiv = el('div', { class: 'icono-tarjeta' })
    iconDiv.appendChild(icono[t.icoFn]())
    tarjeta.appendChild(iconDiv)
    tarjeta.appendChild(el('p', { class: 'numero', id: `num-${t.id}` }, t.numero))
    tarjeta.appendChild(el('p', { class: 'etiqueta' }, t.titulo))
    grid.appendChild(tarjeta)
  })

  return grid
}

async function cargarIndicadores(panel) {
  try {
    const res = await api.get('/panel/resumen')
    document.getElementById('num-hoy').textContent = res.citas_hoy || 0
    document.getElementById('num-programadas').textContent = res.programadas || 0
    document.getElementById('num-atendidas').textContent = res.atendidas || 0
    document.getElementById('num-canceladas').textContent = res.canceladas || 0
    document.getElementById('num-pacientes').textContent = res.pacientes_total || 0
    document.getElementById('num-medicos').textContent = res.medicos_activos || 0
  } catch (error) {
    console.error('Error cargando indicadores:', error)
  }
}

async function cargarCitasHoy(panel) {
  try {
    const res = await api.get('/panel/resumen')
    const citas = res.citas_detalle || []

    if (citas.length === 0) {
      panel.replaceChildren(el('p', {}, 'No hay citas programadas para hoy'))
      return
    }

    const tabla = el('table', { class: 'tabla-citas' })
    const thead = el('thead', {}, [
      el('tr', {}, [
        el('th', {}, 'Código'),
        el('th', {}, 'Paciente'),
        el('th', {}, 'Médico'),
        el('th', {}, 'Hora'),
        el('th', {}, 'Estado'),
      ]),
    ])

    const tbody = el('tbody', {})
    citas.forEach((c) => {
      const hora = c.hora_inicio.substring(0, 5)
      tbody.appendChild(
        el('tr', {}, [
          el('td', {}, c.codigo),
          el('td', {}, c.paciente),
          el('td', {}, c.medico),
          el('td', {}, hora),
          el('td', {}, el('span', { class: `estado-cita ${c.estado.toLowerCase()}` }, c.estado)),
        ])
      )
    })

    tabla.appendChild(thead)
    tabla.appendChild(tbody)
    panel.replaceChildren(tabla)
  } catch (error) {
    panel.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
  }
}

async function cargarBitacora(panel) {
  try {
    const res = await api.get('/seguridad/bitacora/resumen')
    const registros = res.ultimas_acciones || []

    if (registros.length === 0) {
      panel.replaceChildren(el('p', {}, 'No hay registros en la bitácora'))
      return
    }

    const lista = el('ul', { class: 'lista-bitacora' })
    registros.forEach((r) => {
      const fecha = new Date(r.fecha)
      const fechaFormato = fecha.toLocaleDateString() + ' ' + fecha.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      const item = el('li', {}, [
        el('span', { class: 'accion' }, r.accion),
        el('span', { class: 'detalle' }, r.username || 'Sistema'),
        el('span', { class: 'fecha' }, fechaFormato),
      ])
      lista.appendChild(item)
    })

    panel.appendChild(lista)
    panel.appendChild(el('a', { href: '#/seguridad/bitacora', class: 'enlace' }, 'Ver todo →'))
  } catch (error) {
    panel.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
  }
}
