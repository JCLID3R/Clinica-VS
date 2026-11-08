/**
 * CU11 - Cancelar cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { buscarCitas, cancelarCita } from '../api.js'

export default function CU11CancelarCita(contenedor) {
  PantallaCU11(contenedor)
}

// ---- CU11: inicio ----
function PantallaCU11(contenedor) {
  const datos = { citas: [], pagina: 1, total_paginas: 1, total: 0 }

  const pantalla = el('div', { class: 'cu11' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Cancelar cita'),
    ]),
    crearFiltros(datos),
    crearTabla(datos),
    crearPaginacion(datos),
  ])

  contenedor.append(pantalla)
  cargarCitas(datos)
}

function crearFiltros(datos) {
  const inputCI = el('input', { type: 'text', id: 'cu11-ci', placeholder: 'CI del paciente', maxlength: 20 })
  const inputNombre = el('input', { type: 'text', id: 'cu11-nombre', placeholder: 'Nombre del paciente', maxlength: 100 })
  const inputFecha = el('input', { type: 'date', id: 'cu11-fecha' })
  const selectEstado = el('select', { id: 'cu11-estado' }, [
    el('option', { value: '' }, 'Todos los estados'),
    el('option', { value: 'Programada' }, 'Programada'),
    el('option', { value: 'Confirmada' }, 'Confirmada'),
  ])

  const form = el('form', { class: 'formulario' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu11-ci' }, 'CI del paciente'),
      inputCI,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu11-nombre' }, 'Nombre del paciente'),
      inputNombre,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu11-fecha' }, 'Fecha'),
      inputFecha,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu11-estado' }, 'Estado'),
      selectEstado,
    ]),
  ])

  let timeoutBusca = null
  const actualizarBusqueda = () => {
    clearTimeout(timeoutBusca)
    timeoutBusca = setTimeout(() => { datos.pagina = 1; cargarCitas(datos) }, 300)
  }

  inputCI.addEventListener('input', actualizarBusqueda)
  inputNombre.addEventListener('input', actualizarBusqueda)
  inputFecha.addEventListener('change', actualizarBusqueda)
  selectEstado.addEventListener('change', actualizarBusqueda)

  return form
}

function crearTabla(datos) {
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Paciente'),
      el('th', {}, 'CI'),
      el('th', {}, 'Médico'),
      el('th', {}, 'Especialidad'),
      el('th', {}, 'Fecha'),
      el('th', {}, 'Hora'),
      el('th', {}, 'Estado'),
      el('th', { class: 'col-acciones' }, 'Acciones'),
    ])),
    el('tbody', { id: 'cu11-tabla' }, el('tr', {}, el('td', { colSpan: '8', class: 'celda-vacia' }, 'Cargando...'))),
  ])

  return el('div', { class: 'tabla-contenedor' }, tabla)
}

function crearPaginacion(datos) {
  return el('div', { id: 'cu11-paginacion', class: 'paginacion' })
}

async function cargarCitas(datos) {
  const ci = document.getElementById('cu11-ci')?.value.trim() || ''
  const nombre = document.getElementById('cu11-nombre')?.value.trim() || ''
  const fecha = document.getElementById('cu11-fecha')?.value || ''
  const estado = document.getElementById('cu11-estado')?.value || ''

  try {
    // Combinar CI y nombre en un parámetro "buscar"
    let buscar = ''
    if (ci) buscar = ci
    else if (nombre) buscar = nombre

    const params = new URLSearchParams()
    if (buscar) params.append('buscar', buscar)
    if (fecha) params.append('fecha', fecha)
    if (estado) params.append('estado', estado)

    const query = params.toString() ? `?${params.toString()}` : ''
    const resp = await buscarCitas(query)
    datos.citas = resp.citas || []
    datos.total = resp.citas ? resp.citas.length : 0
    datos.total_paginas = 1

    const tbody = document.getElementById('cu11-tabla')
    if (!resp.citas || resp.citas.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '8', class: 'celda-vacia texto-suave' }, 'No hay citas'))
      )
      actualizarPaginacion(datos)
      return
    }

    tbody.replaceChildren(...resp.citas.map(cita => {
      const btnCancelar = el('button', { class: 'btn peligro btn-pequeno', type: 'button' }, 'Cancelar')
      btnCancelar.addEventListener('click', () => mostrarConfirmacionCancelar(cita, datos))

      const claseEstado = `estado-cita ${cita.estado.toLowerCase()}`
      const etiquetaEstado = el('span', { class: claseEstado }, cita.estado)

      // Formatear fecha a DD/MM/YYYY y hora a HH:MM
      const [año, mes, día] = cita.fecha.split('-')
      const fechaFormato = `${día}/${mes}/${año}`
      const horaFormato = cita.hora_inicio.substring(0, 5)

      return el('tr', {}, [
        el('td', {}, cita.paciente),
        el('td', {}, cita.ci),
        el('td', {}, cita.medico),
        el('td', {}, cita.especialidad),
        el('td', {}, fechaFormato),
        el('td', {}, horaFormato),
        el('td', {}, etiquetaEstado),
        el('td', {}, btnCancelar),
      ])
    }))
    actualizarPaginacion(datos)
  } catch (error) {
    const tbody = document.getElementById('cu11-tabla')
    const msg = error instanceof ErrorApi ? error.mensaje : error.message
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '8', class: 'celda-vacia texto-error' }, `Error: ${msg}`))
    )
  }
}

function actualizarPaginacion(datos) {
  const pag = document.getElementById('cu11-paginacion')
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

function mostrarConfirmacionCancelar(cita, datos) {
  const inputMotivo = el('textarea', { placeholder: 'Motivo de la cancelación (requerido)', maxlength: 500 })

  // Formatear fecha a DD/MM/YYYY y hora a HH:MM
  const [año, mes, día] = cita.fecha.split('-')
  const fechaFormato = `${día}/${mes}/${año}`
  const horaFormato = cita.hora_inicio.substring(0, 5)

  const formulario = el('form', { class: 'formulario' }, [
    el('div', { class: 'info-compacta' }, [
      el('p', {}, [el('strong', {}, 'Paciente: '), cita.paciente]),
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
    await cancelarCita(cita.id_cita, { motivo_cancelacion: motivo })
    aviso('Cita cancelada exitosamente', 'ok')
    cerrar()
    cargarCitas(datos)
  } catch (error) {
    const mensaje = error instanceof ErrorApi ? error.mensaje : (error.message || 'Error al cancelar')
    aviso(mensaje, 'error')
  }
}
// ---- CU11: fin ----
