import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { misCitas, cancelarCita } from '../api.js'

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
    datos.proximas = resp.proximas || []
    datos.historial = resp.historial || []

    mostrarProximas(datos)
    mostrarHistorial(datos)
  } catch (error) {
    const msg = error instanceof ErrorApi ? error.mensaje : error.message
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
        el('td', {}, btnCancelar),
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
    const msg = error instanceof ErrorApi ? error.mensaje : (error.message || 'Error al cancelar')
    aviso(msg, 'error')
  }
}
