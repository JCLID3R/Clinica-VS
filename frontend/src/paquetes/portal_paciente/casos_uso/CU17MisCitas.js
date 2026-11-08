import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { misCitas, cancelarCita, horariosDisponibles, reprogramarCita } from '../api.js'
import { SelectorFichas } from '../../agenda/componentes/SelectorFichas.js'

export default function CU17MisCitas(contenedor) {
  PantallaCU17(contenedor)
}

function PantallaCU17(contenedor) {
  const datos = { proximas: [], historial: [] }

  const pantalla = el('div', { class: 'cu17' }, [
    el('div', { class: 'encabezado-pagina' }, el('h1', {}, 'Mis citas')),
    el('div', { class: 'acciones', style: 'margin-bottom: 20px' }, [
      el('a', { href: '#/portal/programar-cita', class: 'btn' }, 'Programar cita'),
    ]),
    el('div', { id: 'cu17-proximas' }),
    el('div', { id: 'cu17-historial' }),
  ])

  contenedor.append(pantalla)
  cargarCitas(datos)
}

async function cargarCitas(datos) {
  try {
    const resp = await misCitas()
    const todas = resp.proximas || []

    const hoy = new Date()
    const año = hoy.getFullYear()
    const mes = String(hoy.getMonth() + 1).padStart(2, '0')
    const día = String(hoy.getDate()).padStart(2, '0')
    const fechaHoy = `${año}-${mes}-${día}`

    datos.proximas = todas.filter(c =>
      (c.estado === 'Programada' || c.estado === 'Confirmada') && c.fecha >= fechaHoy
    )
    datos.historial = todas.filter(c =>
      !(c.estado === 'Programada' || c.estado === 'Confirmada' && c.fecha >= fechaHoy)
    )

    mostrarProximas(datos)
    mostrarHistorial(datos)
  } catch (error) {
    const msg = error instanceof ErrorApi ? error.message : error.message
    aviso(`Error al cargar citas: ${msg}`, 'error')
  }
}

function mostrarProximas(datos) {
  const contenedor = document.getElementById('cu17-proximas')
  contenedor.innerHTML = ''

  const titulo = el('h2', {}, 'Próximas citas')
  contenedor.appendChild(titulo)

  if (!datos.proximas || datos.proximas.length === 0) {
    contenedor.appendChild(el('p', { class: 'texto-suave' }, 'No tienes citas próximas'))
    return
  }

  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Médico'),
      el('th', {}, 'Especialidad'),
      el('th', {}, 'Fecha'),
      el('th', {}, 'Hora'),
      el('th', {}, 'Código'),
      el('th', {}, 'Estado'),
      el('th', { class: 'col-acciones' }, 'Acciones'),
    ])),
    el('tbody', {}, ...datos.proximas.map(cita => {
      const puedeModificar = cita.estado === 'Programada' || cita.estado === 'Confirmada'

      const btnReprogramar = el('button', { class: 'btn btn-pequeno', type: 'button' }, 'Reprogramar')
      btnReprogramar.addEventListener('click', () => mostrarReprogramacion(cita, datos))

      const btnCancelar = el('button', { class: 'btn peligro btn-pequeno', type: 'button' }, 'Cancelar')
      btnCancelar.addEventListener('click', () => mostrarConfirmacionCancelar(cita, datos))

      const claseEstado = `estado-cita ${cita.estado.toLowerCase()}`
      const [año, mes, día] = cita.fecha.split('-')
      const fechaFormato = `${día}/${mes}/${año}`
      const horaFormato = cita.hora_inicio.substring(0, 5)

      return el('tr', {}, [
        el('td', {}, cita.medico),
        el('td', {}, cita.especialidad),
        el('td', {}, fechaFormato),
        el('td', {}, horaFormato),
        el('td', {}, cita.codigo),
        el('td', {}, el('span', { class: claseEstado }, cita.estado)),
        el('td', {}, puedeModificar ? el('div', { class: 'acciones-cita' }, [btnReprogramar, btnCancelar]) : el('div', {})),
      ])
    })),
  ])

  contenedor.appendChild(el('div', { class: 'tabla-contenedor' }, tabla))
}

function mostrarHistorial(datos) {
  const contenedor = document.getElementById('cu17-historial')
  contenedor.innerHTML = ''

  const titulo = el('h2', { style: 'margin-top: 40px' }, 'Historial')
  contenedor.appendChild(titulo)

  if (!datos.historial || datos.historial.length === 0) {
    contenedor.appendChild(el('p', { class: 'texto-suave' }, 'Sin historial'))
    return
  }

  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Médico'),
      el('th', {}, 'Especialidad'),
      el('th', {}, 'Fecha'),
      el('th', {}, 'Hora'),
      el('th', {}, 'Código'),
      el('th', {}, 'Estado'),
    ])),
    el('tbody', {}, ...datos.historial.map(cita => {
      const claseEstado = `estado-cita ${cita.estado.toLowerCase()}`
      const [año, mes, día] = cita.fecha.split('-')
      const fechaFormato = `${día}/${mes}/${año}`
      const horaFormato = cita.hora_inicio.substring(0, 5)

      return el('tr', {}, [
        el('td', {}, cita.medico),
        el('td', {}, cita.especialidad),
        el('td', {}, fechaFormato),
        el('td', {}, horaFormato),
        el('td', {}, cita.codigo),
        el('td', {}, el('span', { class: claseEstado }, cita.estado)),
      ])
    })),
  ])

  contenedor.appendChild(el('div', { class: 'tabla-contenedor' }, tabla))
}

function mostrarReprogramacion(cita, datos) {
  const hoy = new Date()
  const añoMin = hoy.getFullYear()
  const mesMin = String(hoy.getMonth() + 1).padStart(2, '0')
  const díaMin = String(hoy.getDate()).padStart(2, '0')
  const minFecha = `${añoMin}-${mesMin}-${díaMin}`

  const max30 = new Date()
  max30.setDate(max30.getDate() + 30)
  const año30 = max30.getFullYear()
  const mes30 = String(max30.getMonth() + 1).padStart(2, '0')
  const día30 = String(max30.getDate()).padStart(2, '0')
  const maxFecha = `${año30}-${mes30}-${día30}`

  const selectFecha = el('input', { type: 'date', min: minFecha, max: maxFecha })
  const contenedorFichas = el('div', { id: 'cu17-fichas-reprog' })
  const btnCargar = el('button', { type: 'button', class: 'btn' }, 'Cargar fichas')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  btnCargar.addEventListener('click', async () => {
    const fecha = selectFecha.value
    if (!fecha) {
      aviso('Selecciona una fecha', 'error')
      return
    }

    try {
      contenedorFichas.replaceChildren(el('p', {}, 'Cargando fichas...'))
      const res = await horariosDisponibles(cita.id_especialidad, cita.id_medico, fecha)
      if (!res.medicos || res.medicos.length === 0) {
        contenedorFichas.replaceChildren(el('p', { class: 'texto-suave' }, 'No hay disponibilidad ese día'))
        return
      }
      const selector = SelectorFichas(res)
      selector.addEventListener('fichaSeleccionada', (evt) => {
        mostrarConfirmacionReprogramar(cita, fecha, evt.detail.ficha, datos)
      })
      contenedorFichas.replaceChildren(selector)
    } catch (error) {
      const msg = error instanceof ErrorApi ? error.message : (error.message || 'Error desconocido')
      aviso(msg, 'error')
    }
  })

  const [año, mes, día] = cita.fecha.split('-')
  const fechaFormato = `${día}/${mes}/${año}`
  const horaFormato = cita.hora_inicio.substring(0, 5)

  const formulario = el('form', { class: 'formulario' }, [
    el('p', {}, [el('strong', {}, 'Cita actual: '), `${cita.medico} - ${fechaFormato} ${horaFormato}`]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Nueva fecha'),
      selectFecha,
    ]),
    btnCargar,
    el('div', { class: 'mt-20' }, contenedorFichas),
  ])

  const { cerrar } = mostrarModal({
    titulo: 'Reprogramar cita',
    contenido: formulario,
    botones: [btnCancelar],
    primerFoco: selectFecha
  })

  btnCancelar.addEventListener('click', cerrar)
}

function mostrarConfirmacionReprogramar(cita, fecha, ficha, datos) {
  const [año, mes, día] = fecha.split('-')
  const fechaFormato = `${día}/${mes}/${año}`

  const formulario = el('form', { class: 'formulario' }, [
    el('p', {}, [el('strong', {}, 'Cita actual: '), `${cita.medico} - ${cita.fecha} ${cita.hora_inicio.substring(0, 5)}`]),
    el('p', {}, [el('strong', {}, 'Nueva fecha y hora: '), `${fechaFormato} ${ficha.hora}`]),
  ])

  const btnConfirmar = el('button', { type: 'button', class: 'btn' }, 'Confirmar reprogramación')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo: 'Confirmar reprogramación',
    contenido: formulario,
    botones: [btnConfirmar, btnCancelar]
  })

  btnConfirmar.addEventListener('click', () => {
    procederReprogramar(cita, fecha, ficha, datos, cerrar)
  })
  btnCancelar.addEventListener('click', cerrar)
}

async function procederReprogramar(cita, fecha, ficha, datos, cerrar) {
  try {
    await reprogramarCita(cita.id_cita, fecha, ficha.hora, ficha.hora_fin)
    aviso('Cita reprogramada exitosamente', 'ok')
    cerrar()
    setTimeout(() => cargarCitas(datos), 100)
  } catch (error) {
    const msg = error instanceof ErrorApi ? error.message : (error.message || 'Error al reprogramar')
    aviso(msg, 'error')
  }
}

function mostrarConfirmacionCancelar(cita, datos) {
  const inputMotivo = el('textarea', { placeholder: 'Motivo de la cancelación (requerido)', maxlength: 500 })

  const [año, mes, día] = cita.fecha.split('-')
  const fechaFormato = `${día}/${mes}/${año}`
  const horaFormato = cita.hora_inicio.substring(0, 5)

  const formulario = el('form', { class: 'formulario' }, [
    el('div', { class: 'info-compacta' }, [
      el('p', {}, [el('strong', {}, 'Médico: '), cita.medico]),
      el('p', {}, [el('strong', {}, 'Especialidad: '), cita.especialidad]),
      el('p', {}, [el('strong', {}, 'Fecha: '), `${fechaFormato} ${horaFormato}`]),
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Motivo *'),
      inputMotivo,
    ]),
  ])

  const btnConfirmar = el('button', { type: 'button', class: 'btn peligro' }, 'Confirmar cancelación')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo: 'Cancelar cita',
    contenido: formulario,
    botones: [btnConfirmar, btnCancelar],
    primerFoco: inputMotivo
  })

  btnConfirmar.addEventListener('click', () => {
    procederCancelar(cita, inputMotivo.value.trim(), datos, cerrar)
  })
  btnCancelar.addEventListener('click', cerrar)
}

async function procederCancelar(cita, motivo, datos, cerrar) {
  if (!motivo) {
    aviso('El motivo es requerido', 'error')
    return
  }

  try {
    await cancelarCita(cita.id_cita, motivo)
    aviso('Cita cancelada exitosamente', 'ok')
    cerrar()
    cargarCitas(datos)
  } catch (error) {
    const msg = error instanceof ErrorApi ? error.message : (error.message || 'Error al cancelar')
    aviso(msg, 'error')
  }
}
