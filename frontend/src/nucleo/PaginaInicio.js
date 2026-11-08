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

  const rol = usuario.rol

  if (rol === 'Paciente') {
    const panelProxima = el('div', { id: 'proxima-cita' })
    const panelNombrePaciente = el('h1', {}, `${saludo}, ${usuario.username}`)

    api.get('/portal/perfil').then(res => {
      const paciente = res.paciente || {}
      const nombrePaciente = paciente.nombres ? paciente.nombres.split(' ')[0] : usuario.username
      panelNombrePaciente.textContent = `${saludo}, ${nombrePaciente}`
    }).catch(() => {})

    contenedor.replaceChildren(
      el('div', { class: 'panel' }, [
        panelNombrePaciente,
        el('p', { class: 'subtitulo' }, 'Portal del paciente'),
        el('div', { class: 'acciones', style: 'margin-bottom: 20px;' }, [
          el('a', { href: '#/portal/programar-cita', class: 'btn' }, 'Programar cita'),
          el('a', { href: '#/portal/mis-citas', class: 'btn secundario' }, 'Ver mis citas'),
          el('a', { href: '#/portal/perfil', class: 'btn secundario' }, 'Mi perfil'),
        ]),
        el('h3', {}, 'Próxima cita'),
        panelProxima,
      ])
    )
    cargarProximaCitaPaciente(panelProxima)
    return
  }

  if (rol === 'Medico') {
    const panelCitasHoy = el('div', { id: 'citas-hoy' })
    const panelProxima = el('div', { id: 'proxima-cita' })
    contenedor.replaceChildren(
      el('div', { class: 'panel' }, [
        el('h1', {}, `${saludo}, ${usuario.username}`),
        el('p', { class: 'subtitulo' }, 'Panel del médico'),
        el('div', { class: 'acciones', style: 'margin-bottom: 20px;' }, [
          el('a', { href: '#/agenda/consultar-disponibilidad', class: 'btn' }, 'Agenda médica'),
        ]),
        el('h3', {}, 'Próxima cita'),
        panelProxima,
        el('h3', {}, 'Citas de hoy'),
        panelCitasHoy,
      ])
    )
    cargarProximaCitaMedico(panelProxima)
    cargarCitasHoy(panelCitasHoy)
    return
  }

  if (rol === 'Recepcionista') {
    const panelIndicadores = crearPanelIndicadoresRecepcionista()
    const panelCitasHoy = el('div', { id: 'citas-hoy' })
    contenedor.replaceChildren(
      el('div', { class: 'panel' }, [
        el('h1', {}, `${saludo}, ${usuario.username}`),
        el('p', { class: 'subtitulo' }, 'Resumen de gestión'),
        panelIndicadores,
        el('h3', {}, 'Citas de hoy'),
        panelCitasHoy,
      ])
    )
    cargarIndicadoresRecepcionista(panelIndicadores)
    cargarCitasHoy(panelCitasHoy)
    return
  }

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

async function cargarProximaCitaPaciente(panel) {
  try {
    const res = await api.get('/portal/citas')
    if (!res.proximas || res.proximas.length === 0) {
      panel.replaceChildren(el('p', { class: 'suave' }, 'No tienes citas próximas'))
      return
    }

    const hoy = new Date()
    const año = hoy.getFullYear()
    const mes = String(hoy.getMonth() + 1).padStart(2, '0')
    const día = String(hoy.getDate()).padStart(2, '0')
    const fechaHoy = `${año}-${mes}-${día}`
    const horaAhora = String(hoy.getHours()).padStart(2, '0') + ':' + String(hoy.getMinutes()).padStart(2, '0')

    const cita = res.proximas.find(c =>
      (c.estado === 'Programada' || c.estado === 'Confirmada') &&
      (c.fecha > fechaHoy || (c.fecha === fechaHoy && c.hora_inicio > horaAhora))
    )

    if (!cita) {
      panel.replaceChildren(el('p', { class: 'suave' }, 'No tienes citas próximas'))
      return
    }

    const [citaAño, citaMes, citaDía] = cita.fecha.split('-')
    const fechaFormato = `${citaDía}/${citaMes}/${citaAño}`
    panel.replaceChildren(
      el('div', { class: 'tarjeta', style: 'padding: 16px;' }, [
        el('p', {}, [el('strong', {}, 'Médico: '), cita.medico]),
        el('p', {}, [el('strong', {}, 'Especialidad: '), cita.especialidad]),
        el('p', {}, [el('strong', {}, 'Fecha: '), `${fechaFormato} ${cita.hora_inicio.slice(0, 5)}`]),
        el('p', {}, [el('strong', {}, 'Código: '), cita.codigo]),
      ])
    )
  } catch (error) {
    panel.replaceChildren(el('p', { class: 'suave' }, 'Error al cargar citas'))
  }
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

function crearPanelIndicadoresRecepcionista() {
  const grid = el('div', { class: 'tarjetas' })

  const tarjetas = [
    { id: 'hoy', titulo: 'Citas hoy', numero: '-', icoFn: 'calendario' },
    { id: 'programadas', titulo: 'Programadas', numero: '-', icoFn: 'programarCita' },
    { id: 'confirmadas', titulo: 'Confirmadas', numero: '-', icoFn: 'asistencia' },
    { id: 'canceladas', titulo: 'Inasistencias', numero: '-', icoFn: 'cerrar' },
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

async function cargarIndicadoresRecepcionista(panel) {
  try {
    const res = await api.get('/panel/resumen')
    document.getElementById('num-hoy').textContent = res.citas_hoy || 0
    document.getElementById('num-programadas').textContent = res.programadas || 0
    document.getElementById('num-confirmadas').textContent = res.confirmadas || 0
    document.getElementById('num-canceladas').textContent = res.inasistencias || 0
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

async function cargarProximaCitaMedico(panel) {
  try {
    const hoy = new Date()
    const añoHoy = hoy.getFullYear()
    const mesHoy = String(hoy.getMonth() + 1).padStart(2, '0')
    const díaHoy = String(hoy.getDate()).padStart(2, '0')
    const fechaHoy = `${añoHoy}-${mesHoy}-${díaHoy}`

    const max30 = new Date()
    max30.setDate(max30.getDate() + 30)
    const año30 = max30.getFullYear()
    const mes30 = String(max30.getMonth() + 1).padStart(2, '0')
    const día30 = String(max30.getDate()).padStart(2, '0')
    const fecha30 = `${año30}-${mes30}-${día30}`

    const res = await api.get(`/citas/agenda?fecha_desde=${fechaHoy}&fecha_hasta=${fecha30}`)
    const citas = res.citas || []
    const proximaCita = citas.find(c => c.estado === 'Programada' || c.estado === 'Confirmada')

    if (!proximaCita) {
      panel.replaceChildren(el('p', { class: 'suave' }, 'No tiene citas próximas'))
      return
    }

    const [año, mes, día] = proximaCita.fecha.split('-')
    const fechaFormato = `${día}/${mes}/${año}`
    panel.replaceChildren(
      el('div', { class: 'tarjeta', style: 'padding: 16px;' }, [
        el('p', {}, [el('strong', {}, 'Paciente: '), proximaCita.paciente]),
        el('p', {}, [el('strong', {}, 'Especialidad: '), proximaCita.especialidad]),
        el('p', {}, [el('strong', {}, 'Fecha: '), `${fechaFormato} ${proximaCita.hora_inicio.slice(0, 5)}`]),
        el('p', {}, [el('strong', {}, 'Código: '), proximaCita.codigo]),
      ])
    )
  } catch (error) {
    panel.replaceChildren(el('p', { class: 'suave' }, 'Error al cargar citas'))
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
