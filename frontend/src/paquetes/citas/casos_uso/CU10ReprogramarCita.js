/**
 * CU10 - Reprogramar cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { buscarCitas, reprogramarCita } from '../api.js'
import { consultarDisponibilidad } from '../../agenda/api.js'
import { SelectorFichas } from '../../agenda/componentes/SelectorFichas.js'

export default function CU10ReprogramarCita(contenedor) {
  PantallaCU10(contenedor)
}

// ---- CU10: inicio ----
function PantallaCU10(contenedor) {
  const datos = { cita: null, citas: [], pagina: 1, total_paginas: 1, total: 0 }
  const params = new URLSearchParams(window.location.hash.split('?')[1])
  const citaParam = params.get('cita')

  const pantalla = el('div', { class: 'cu10' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Reprogramar cita'),
    ]),
    el('div', { id: 'cu10-contenido' }),
  ])

  contenedor.append(pantalla)

  if (citaParam) {
    datos.citaId = parseInt(citaParam)
    buscarCitaDirecta(datos, pantalla)
  } else {
    mostrarBusqueda(datos, pantalla)
  }
}

function mostrarBusqueda(datos, pantalla) {
  const contenido = document.getElementById('cu10-contenido')

  const inputCI = el('input', { type: 'text', id: 'cu10-ci', placeholder: 'CI del paciente', maxlength: 20 })
  const inputNombre = el('input', { type: 'text', id: 'cu10-nombre', placeholder: 'Nombre del paciente', maxlength: 100 })
  const inputFecha = el('input', { type: 'date', id: 'cu10-fecha' })

  const form = el('form', { class: 'formulario' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu10-ci' }, 'CI del paciente'),
      inputCI,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu10-nombre' }, 'Nombre del paciente'),
      inputNombre,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu10-fecha' }, 'Fecha (opcional)'),
      inputFecha,
    ]),
  ])

  const tablaCitas = el('div', { class: 'tabla-contenedor' }, [
    el('table', { class: 'tabla' }, [
      el('thead', {}, el('tr', {}, [
        el('th', {}, 'Paciente'),
        el('th', {}, 'CI'),
        el('th', {}, 'Fecha'),
        el('th', {}, 'Hora'),
        el('th', {}, 'Estado'),
        el('th', { class: 'col-acciones' }, 'Acciones'),
      ])),
      el('tbody', { id: 'cu10-tabla' }, el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia' }, 'Ingresa filtros para buscar citas'))),
    ]),
  ])

  const paginacion = el('div', { id: 'cu10-paginacion', class: 'paginacion' })

  contenido.replaceChildren(form, tablaCitas, paginacion)

  let timeoutBusca = null
  const actualizarBusqueda = () => {
    clearTimeout(timeoutBusca)
    timeoutBusca = setTimeout(() => { datos.pagina = 1; cargarCitas(datos) }, 300)
  }

  inputCI.addEventListener('input', actualizarBusqueda)
  inputNombre.addEventListener('input', actualizarBusqueda)
  inputFecha.addEventListener('change', actualizarBusqueda)
}

async function cargarCitas(datos) {
  const ci = document.getElementById('cu10-ci')?.value.trim() || ''
  const nombre = document.getElementById('cu10-nombre')?.value.trim() || ''
  const fecha = document.getElementById('cu10-fecha')?.value || ''

  if (!ci && !nombre && !fecha) {
    const tbody = document.getElementById('cu10-tabla')
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia texto-suave' }, 'Ingresa filtros para buscar citas'))
    )
    return
  }

  try {
    // Combinar CI y nombre en un parámetro "buscar"
    let buscar = ''
    if (ci) buscar = ci
    else if (nombre) buscar = nombre

    const params = new URLSearchParams()
    if (buscar) params.append('buscar', buscar)
    if (fecha) params.append('fecha', fecha)

    const query = params.toString() ? `?${params.toString()}` : ''
    const resp = await buscarCitas(query)
    datos.citas = resp.citas || []
    datos.total = resp.citas ? resp.citas.length : 0
    datos.total_paginas = 1

    const tbody = document.getElementById('cu10-tabla')
    if (!resp.citas || resp.citas.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia texto-suave' }, 'No hay citas'))
      )
      actualizarPaginacion(datos)
      return
    }

    tbody.replaceChildren(...resp.citas.map(cita => {
      const btnReprogramar = el('button', { class: 'btn btn-pequeno', type: 'button' }, 'Seleccionar')
      btnReprogramar.addEventListener('click', () => {
        datos.cita = cita
        mostrarReprogramacion(datos)
      })

      const claseEstado = `estado-cita ${cita.estado.toLowerCase()}`
      const etiquetaEstado = el('span', { class: claseEstado }, cita.estado)
      const horaFormato = cita.hora_inicio.substring(0, 5)

      return el('tr', {}, [
        el('td', {}, cita.paciente),
        el('td', {}, cita.ci),
        el('td', {}, cita.fecha),
        el('td', {}, horaFormato),
        el('td', {}, etiquetaEstado),
        el('td', {}, btnReprogramar),
      ])
    }))
    actualizarPaginacion(datos)
  } catch (error) {
    const tbody = document.getElementById('cu10-tabla')
    const msg = error instanceof ErrorApi ? error.mensaje : error.message
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia texto-error' }, `Error: ${msg}`))
    )
  }
}

function actualizarPaginacion(datos) {
  const pag = document.getElementById('cu10-paginacion')
  pag.replaceChildren()

  const btnAnterior = el('button', { class: 'btn secundario', disabled: datos.pagina === 1 }, 'Anterior')
  const btnSiguiente = el('button', { class: 'btn secundario', disabled: datos.pagina === datos.total_paginas }, 'Siguiente')
  const txtInfo = el('span', { class: 'info-paginacion' }, `Página ${datos.pagina} de ${datos.total_paginas} (${datos.total} total)`)

  btnAnterior.addEventListener('click', () => {
    if (datos.pagina > 1) { datos.pagina--; cargarCitas(datos) }
  })
  btnSiguiente.addEventListener('click', () => {
    if (datos.pagina < datos.total_paginas) { datos.pagina++; cargarCitas(datos) }
  })

  pag.append(btnAnterior, txtInfo, btnSiguiente)
}

async function buscarCitaDirecta(datos, pantalla) {
  try {
    const params = new URLSearchParams()
    params.append('id', datos.citaId)
    const query = `?${params.toString()}`
    const resp = await buscarCitas(query)
    if (resp.citas && resp.citas.length > 0) {
      datos.cita = resp.citas[0]
      mostrarReprogramacion(datos)
    } else {
      aviso('Cita no encontrada', 'error')
    }
  } catch (error) {
    const msg = error instanceof ErrorApi ? error.mensaje : error.message
    aviso(`Error: ${msg}`, 'error')
  }
}

function hoyLocal() {
  const d = new Date()
  const año = d.getFullYear()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const día = String(d.getDate()).padStart(2, '0')
  return `${año}-${mes}-${día}`
}

function sumarDías(días) {
  const d = new Date()
  d.setDate(d.getDate() + días)
  const año = d.getFullYear()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const día = String(d.getDate()).padStart(2, '0')
  return `${año}-${mes}-${día}`
}

function mostrarReprogramacion(datos) {
  const contenido = document.getElementById('cu10-contenido')
  const cita = datos.cita

  const hoy = hoyLocal()
  const max30 = sumarDías(30)

  const selectFecha = el('input', { type: 'date', min: hoy, max: max30 })
  const contenedorFichas = el('div', { id: 'cu10-fichas' })
  const btnCargar = el('button', { type: 'button', class: 'btn' }, 'Cargar fichas disponibles')
  const btnAtras = el('button', { type: 'button', class: 'btn secundario' }, 'Atrás')

  btnCargar.addEventListener('click', async () => {
    const fecha = selectFecha.value
    if (!fecha) {
      contenedorFichas.replaceChildren(el('p', { class: 'texto-error' }, 'Selecciona una fecha'))
      return
    }

    try {
      const res = await consultarDisponibilidad({
        id_especialidad: cita.id_especialidad || cita.especialidad_id,
        id_medico: cita.id_medico,
        fecha
      })
      if (!res.medicos || res.medicos.length === 0) {
        contenedorFichas.replaceChildren(el('p', { class: 'texto-suave' }, 'No hay disponibilidad ese día'))
        return
      }
      const selector = SelectorFichas(res)
      selector.addEventListener('fichaSeleccionada', (evt) => {
        mostrarConfirmacion(cita, fecha, evt.detail.ficha, datos)
      })
      contenedorFichas.replaceChildren(selector)
    } catch (error) {
      const msg = error instanceof ErrorApi ? error.mensaje : (error.message || 'Error desconocido')
      contenedorFichas.replaceChildren(el('p', { class: 'texto-error' }, `Error: ${msg}`))
    }
  })

  btnAtras.addEventListener('click', () => {
    window.location.hash = '#/citas/reprogramar'
    location.reload()
  })

  contenido.replaceChildren(
    el('p', { class: 'mb-20' }, [
      el('strong', {}, 'Cita actual: '),
      `${cita.paciente} - ${cita.medico} - ${cita.fecha} ${cita.hora_inicio.substring(0, 5)}`,
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Nueva fecha'),
      selectFecha,
    ]),
    btnCargar,
    el('div', { class: 'mt-20' }, contenedorFichas),
    el('div', { class: 'mt-20 flex-gap-10' }, btnAtras)
  )
}

function mostrarConfirmacion(citaActual, fecha, ficha, datos) {
  const formulario = el('form', { class: 'formulario' }, [
    el('p', {}, `Paciente: ${citaActual.paciente}`),
    el('p', {}, `Cita actual: ${citaActual.fecha} ${citaActual.hora_inicio.substring(0, 5)}`),
    el('p', {}, `Nueva fecha: ${fecha}`),
    el('p', {}, `Ficha: ${ficha.numero} (${ficha.hora})`),
  ])

  const btnConfirmar = el('button', { type: 'button', class: 'btn' }, 'Confirmar reprogramación')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo: 'Confirmar reprogramación',
    contenido: formulario,
    botones: [btnConfirmar, btnCancelar]
  })

  btnConfirmar.addEventListener('click', () => {
    procederReprogramar(citaActual, fecha, ficha, datos, cerrar)
  })
  btnCancelar.addEventListener('click', cerrar)
}

async function procederReprogramar(citaActual, fecha, ficha, datos, cerrar) {
  try {
    const resp = await reprogramarCita(citaActual.id_cita, {
      fecha,
      hora_inicio: ficha.hora,
      hora_fin: ficha.hora_fin,
    })

    cerrar()
    // Agregar fechas anterior y nueva para el comprobante
    resp.comprobante.fecha_anterior = citaActual.fecha
    resp.comprobante.fecha_nueva = resp.comprobante.fecha
    mostrarComprobante(resp.comprobante)
  } catch (error) {
    const mensaje = error instanceof ErrorApi ? error.mensaje : (error.message || 'Error al reprogramar')
    aviso(mensaje, 'error')
  }
}

function mostrarComprobante(comprobante) {
  const contenido = document.getElementById('cu10-contenido')

  // Formatear fechas a DD/MM/YYYY
  const formatearFecha = (fecha) => {
    const [año, mes, día] = fecha.split('-')
    return `${día}/${mes}/${año}`
  }

  // Formatear horario: quitar segundos (de "HH:MM:SS - HH:MM:SS" a "HH:MM - HH:MM")
  const formatearHorario = (horario) => {
    return horario.split(' - ').map(h => h.substring(0, 5)).join(' - ')
  }

  const btnNueva = el('button', { type: 'button', class: 'btn' }, 'Reprogramar otra cita')
  btnNueva.addEventListener('click', () => {
    window.location.hash = '#/citas/reprogramar'
    location.reload()
  })

  contenido.replaceChildren(
    el('h2', { class: 'encabezado-pagina' }, 'Cita reprogramada'),
    el('div', { class: 'comprobante' }, [
      el('p', {}, [el('strong', {}, 'Código: '), comprobante.codigo]),
      el('p', {}, [el('strong', {}, 'Paciente: '), comprobante.paciente]),
      el('p', {}, [el('strong', {}, 'Especialidad: '), comprobante.especialidad]),
      el('p', {}, [el('strong', {}, 'Médico: '), comprobante.medico]),
      el('p', {}, [el('strong', {}, 'Fecha anterior: '), formatearFecha(comprobante.fecha_anterior)]),
      el('p', {}, [el('strong', {}, 'Fecha nueva: '), formatearFecha(comprobante.fecha_nueva)]),
      el('p', {}, [el('strong', {}, 'Horario: '), formatearHorario(comprobante.horario)]),
    ]),
    btnNueva
  )
}
// ---- CU10: fin ----
