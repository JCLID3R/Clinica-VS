/**
 * CU12 - Registrar asistencia de la cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista, Médico
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { citasAsistencia, accionesValidas, cambiarEstadoCita, obtenerInasistenciasPendientes, marcarInasistencias } from '../api.js'
import { listarMedicos } from '../../personas/api.js'

export default function CU12RegistrarAsistencia(contenedor) {
  PantallaCU12(contenedor)
}

// ---- CU12: inicio ----
function PantallaCU12(contenedor) {
  const datos = { citas: [], medicos: [], fecha: hoy(), medico: null, pagina: 1, total_paginas: 1, total: 0 }

  const pantalla = el('div', { class: 'cu12' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Registrar asistencia de citas'),
      el('div', { class: 'acciones' }, [
        el('button', { class: 'btn secundario', id: 'cu12-inasistencias' }, 'Marcar inasistencias pendientes'),
      ]),
    ]),
    crearFiltros(datos),
    crearTabla(datos),
    crearPaginacion(datos),
  ])

  contenedor.append(pantalla)

  document.getElementById('cu12-inasistencias').addEventListener('click', () => {
    mostrarInasistenciasPendientes(datos)
  })

  cargarMedicos(datos)
  cargarCitas(datos)
}

function hoy() {
  const d = new Date()
  const año = d.getFullYear()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const día = String(d.getDate()).padStart(2, '0')
  return `${año}-${mes}-${día}`
}

function crearFiltros(datos) {
  const inputFecha = el('input', { type: 'date', id: 'cu12-fecha', value: datos.fecha })
  const selectMedico = el('select', { id: 'cu12-medico' }, [
    el('option', { value: '' }, 'Todos los médicos'),
  ])

  const form = el('form', { class: 'formulario' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu12-fecha' }, 'Fecha'),
      inputFecha,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu12-medico' }, 'Médico'),
      selectMedico,
    ]),
  ])

  inputFecha.addEventListener('change', () => {
    datos.fecha = inputFecha.value
    datos.pagina = 1
    cargarCitas(datos)
  })

  selectMedico.addEventListener('change', () => {
    datos.medico = selectMedico.value || null
    datos.pagina = 1
    cargarCitas(datos)
  })

  return form
}

function crearTabla(datos) {
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Paciente'),
      el('th', {}, 'CI'),
      el('th', {}, 'Médico'),
      el('th', {}, 'Hora'),
      el('th', {}, 'Estado'),
      el('th', { class: 'col-acciones' }, 'Acciones'),
    ])),
    el('tbody', { id: 'cu12-tabla' }, el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia' }, 'Cargando...'))),
  ])

  return el('div', { class: 'tabla-contenedor' }, tabla)
}

function crearPaginacion(datos) {
  return el('div', { id: 'cu12-paginacion', class: 'paginacion' })
}

async function cargarMedicos(datos) {
  try {
    const resp = await listarMedicos('?estado=activo&por_pagina=100')
    const selectMedico = document.getElementById('cu12-medico')
    resp.medicos.forEach(med => {
      selectMedico.appendChild(el('option', { value: med.id_medico }, `${med.nombres} ${med.apellidos}`))
    })
    datos.medicos = resp.medicos
  } catch (error) {
    console.error('Error cargando médicos:', error.message)
  }
}

async function cargarCitas(datos) {
  try {
    const params = new URLSearchParams()
    params.append('fecha', datos.fecha)
    if (datos.medico) params.append('id_medico', datos.medico)
    params.append('pagina', datos.pagina)
    params.append('por_pagina', '20')

    const query = `?${params.toString()}`
    const resp = await citasAsistencia(query)
    datos.citas = resp.citas
    datos.total = resp.total
    datos.total_paginas = resp.total_paginas

    const tbody = document.getElementById('cu12-tabla')
    if (!resp.citas || resp.citas.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia texto-suave' }, 'No hay citas para esta fecha'))
      )
      actualizarPaginacion(datos)
      return
    }

    tbody.replaceChildren(...resp.citas.map(cita => {
      const claseEstado = `estado-cita ${cita.estado.toLowerCase()}`
      const etiquetaEstado = el('span', { class: claseEstado }, cita.estado)
      const horaFormato = cita.hora_inicio.substring(0, 5)

      return el('tr', {}, [
        el('td', {}, cita.paciente),
        el('td', {}, cita.ci),
        el('td', {}, cita.medico),
        el('td', {}, horaFormato),
        el('td', {}, etiquetaEstado),
        cargarAccionesCita(cita, datos),
      ])
    }))
    actualizarPaginacion(datos)
  } catch (error) {
    const tbody = document.getElementById('cu12-tabla')
    const msg = error instanceof ErrorApi ? error.mensaje : (error.message || String(error))
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia texto-error' }, `Error: ${msg}`))
    )
  }
}

function cargarAccionesCita(cita, datos) {
  const celdaAcciones = el('td', { class: 'flex-acciones' })

  accionesValidas(cita.id_cita)
    .then(resp => {
      if (resp.acciones && resp.acciones.length > 0) {
        resp.acciones.forEach(accion => {
          const btn = el('button', {
            type: 'button',
            class: obtenerClaseBoton(accion.accion) + ' btn-acciones'
          }, accion.label || obtenerLabelAccion(accion.accion))

          btn.addEventListener('click', () => {
            ejecutarAccion(cita, accion.nuevoEstado || accion.accion, datos)
          })

          celdaAcciones.appendChild(btn)
        })
      } else {
        celdaAcciones.appendChild(el('span', { class: 'texto-suave', style: 'font-size: 12px;' }, 'Sin acciones'))
      }
    })
    .catch(() => {
      celdaAcciones.appendChild(el('span', { class: 'texto-error', style: 'font-size: 12px;' }, 'Error'))
    })

  return celdaAcciones
}

function obtenerClaseBoton(accion) {
  if (accion === 'Atendida') return 'btn'
  if (accion === 'Confirmar') return 'btn'
  if (accion === 'Inasistencia') return 'btn peligro'
  return 'btn secundario'
}

function obtenerLabelAccion(accion) {
  if (accion === 'Atendida') return 'Atendida'
  if (accion === 'Confirmar') return 'Confirmar llegada'
  if (accion === 'Inasistencia') return 'Inasistencia'
  return accion
}

async function ejecutarAccion(cita, accion, datos) {
  try {
    await cambiarEstadoCita(cita.id_cita, { estado: accion })
    const mensajeAccion = accion === 'Atendida' ? 'marcada como atendida' : accion === 'Confirmar' ? 'confirmada' : 'marcada como inasistencia'
    aviso(`Cita ${mensajeAccion}.`, 'ok')
    cargarCitas(datos)
  } catch (error) {
    const mensaje = error instanceof ErrorApi ? error.mensaje : (error.message || 'Error al actualizar')
    aviso(mensaje, 'error')
  }
}

function actualizarPaginacion(datos) {
  const pag = document.getElementById('cu12-paginacion')
  pag.replaceChildren()

  const total = datos.citas ? datos.citas.length : 0
  if (total > 0) {
    const txtInfo = el('span', { class: 'info-paginacion' }, `Total: ${total} cita${total !== 1 ? 's' : ''}`)
    pag.append(txtInfo)
  }
}

async function mostrarInasistenciasPendientes(datos) {
  try {
    const resp = await obtenerInasistenciasPendientes()
    const citas = resp.citas || []

    if (citas.length === 0) {
      aviso('No hay inasistencias pendientes', 'ok')
      return
    }

    const listaCitas = el('div', { class: 'scroll-max-300' },
      citas.map(c =>
        el('div', { class: 'item-lista' }, [
          el('p', { class: 'item-nombres' }, `${c.paciente_nombres} ${c.paciente_apellidos}`),
          el('p', { class: 'item-detalles' }, `${c.fecha} - ${c.hora_inicio}`),
        ])
      )
    )

    const btnConfirmar = el('button', { type: 'button', class: 'btn peligro' }, 'Confirmar')
    const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

    const { cerrar } = mostrarModal({
      titulo: `Marcar ${citas.length} inasistencias`,
      contenido: listaCitas,
      botones: [btnConfirmar, btnCancelar]
    })

    btnConfirmar.addEventListener('click', async () => {
      cerrar()
      try {
        const ids = citas.map(c => c.id_cita)
        await marcarInasistencias({ ids })
        aviso(`${citas.length} inasistencias marcadas.`, 'ok')
        cargarCitas(datos)
      } catch (error) {
        const msg = error instanceof ErrorApi ? error.mensaje : (error.message || 'Error al marcar')
        aviso(msg, 'error')
      }
    })

    btnCancelar.addEventListener('click', cerrar)
  } catch (error) {
    const msg = error instanceof ErrorApi ? error.mensaje : error.message
    aviso(`Error: ${msg}`, 'error')
  }
}
// ---- CU12: fin ----
