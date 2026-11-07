/**
 * CU14 - Consultar historial de citas del paciente  (paquete citas, Ciclo 2)
 * Actores: Recepcionista, Médico, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { buscarPacientesParaHistorial, obtenerHistorialPaciente, obtenerCambiosCita } from '../api.js'

export default function CU14ConsultarHistorialPaciente(contenedor) {
  PantallaCU14(contenedor)
}

function PantallaCU14(contenedor) {
  const datos = { paciente: null, citas: [], resumen: null, filtroEstado: '', filtroDesde: '', filtroHasta: '' }

  const btnVolver = el('button', { class: 'btn secundario', id: 'cu14-volver', style: 'display: none;' }, '← Volver')

  const pantalla = el('div', { class: 'cu14' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Historial de citas del paciente'),
      btnVolver,
    ]),
    el('div', { id: 'cu14-contenido' }),
  ])

  contenedor.append(pantalla)

  // Revisar si viene id_paciente en el hash
  const params = new URLSearchParams(window.location.hash.split('?')[1])
  const idPacienteParam = params.get('id_paciente')

  if (idPacienteParam) {
    // Cargar directamente el historial de ese paciente
    datos.paciente = { id_paciente: parseInt(idPacienteParam), nombres: '', apellidos: '', ci: '' }
    cargarHistorial(datos)
  } else {
    mostrarBusqueda(datos)
  }
}

function mostrarBusqueda(datos) {
  const contenido = document.getElementById('cu14-contenido')
  document.getElementById('cu14-volver').style.display = 'none'

  const inputBusqueda = el('input', { type: 'text', id: 'cu14-busqueda', placeholder: 'CI o nombre del paciente', maxlength: 100 })
  const formularioBusqueda = el('div', { class: 'campo', style: 'margin-bottom: 20px;' }, [
    el('label', { for: 'cu14-busqueda' }, 'Buscar'),
    inputBusqueda,
  ])
  const tablaPacientes = el('div', { class: 'tabla-contenedor' }, [
    el('table', { class: 'tabla' }, [
      el('thead', {}, el('tr', {}, [
        el('th', {}, 'Paciente'),
        el('th', {}, 'CI'),
        el('th', { class: 'col-acciones' }, 'Acciones'),
      ])),
      el('tbody', { id: 'cu14-tabla' }, el('tr', {}, el('td', { colSpan: '3', class: 'celda-vacia' }, 'Ingresa el CI o nombre del paciente'))),
    ]),
  ])

  contenido.replaceChildren(formularioBusqueda, tablaPacientes)

  let timeoutBusca = null
  inputBusqueda.addEventListener('input', () => {
    clearTimeout(timeoutBusca)
    timeoutBusca = setTimeout(() => hacerBusquedaPaciente(datos), 300)
  })
}

async function hacerBusquedaPaciente(datos) {
  const busqueda = document.getElementById('cu14-busqueda')?.value.trim() || ''
  const tbody = document.getElementById('cu14-tabla')

  if (!busqueda) {
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '3', class: 'celda-vacia' }, 'Ingresa el CI o nombre del paciente'))
    )
    return
  }

  try {
    const resp = await buscarPacientesParaHistorial(`?buscar=${encodeURIComponent(busqueda)}`)
    const pacientes = resp.pacientes || []

    if (pacientes.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '3', class: 'celda-vacia' }, 'No se encontraron pacientes'))
      )
      return
    }

    tbody.replaceChildren(...pacientes.map(paciente => {
      const btnSeleccionar = el('button', { class: 'btn btn-pequeno', type: 'button' }, 'Ver historial')
      btnSeleccionar.addEventListener('click', () => {
        datos.paciente = paciente
        cargarHistorial(datos)
      })

      return el('tr', {}, [
        el('td', {}, paciente.nombres + ' ' + paciente.apellidos),
        el('td', {}, paciente.ci),
        el('td', {}, btnSeleccionar),
      ])
    }))
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al buscar paciente')
    aviso(msg, 'error')
  }
}

async function cargarHistorial(datos) {
  const contenido = document.getElementById('cu14-contenido')
  document.getElementById('cu14-volver').style.display = 'block'

  try {
    const query = new URLSearchParams()
    if (datos.filtroEstado) query.append('estado', datos.filtroEstado)
    if (datos.filtroDesde) query.append('desde', datos.filtroDesde)
    if (datos.filtroHasta) query.append('hasta', datos.filtroHasta)

    const resp = await obtenerHistorialPaciente(datos.paciente.id_paciente, '?' + query.toString())
    datos.citas = resp.citas || []
    datos.resumen = resp.resumen || {}

    const edad = datos.paciente.edad ? ` (${datos.paciente.edad} años)` : ''
    const vistaPaciente = el('div', { class: 'tarjeta', style: 'margin-bottom: 20px; padding: 15px;' }, [
      el('h2', { style: 'margin: 0 0 10px 0;' }, datos.paciente.nombres + ' ' + datos.paciente.apellidos + edad),
      el('p', { style: 'margin: 5px 0;' }, 'CI: ' + (datos.paciente.ci || 'N/A')),
      el('p', { style: 'margin: 5px 0;' }, 'Teléfono: ' + (datos.paciente.telefono || 'N/A')),
    ])

    const filtros = el('form', { class: 'formulario-filtros' }, [
      el('div', { class: 'campo' }, [
        el('label', {}, 'Estado'),
        el('select', { id: 'cu14-estado' }, [
          el('option', { value: '' }, 'Todos'),
          el('option', { value: 'Programada' }, 'Programada'),
          el('option', { value: 'Confirmada' }, 'Confirmada'),
          el('option', { value: 'Atendida' }, 'Atendida'),
          el('option', { value: 'Cancelada' }, 'Cancelada'),
          el('option', { value: 'Inasistencia' }, 'Inasistencia'),
        ]),
      ]),
      el('div', { class: 'campo' }, [
        el('label', {}, 'Desde'),
        el('input', { type: 'date', id: 'cu14-desde' }),
      ]),
      el('div', { class: 'campo' }, [
        el('label', {}, 'Hasta'),
        el('input', { type: 'date', id: 'cu14-hasta' }),
      ]),
    ])

    const resumenDiv = el('div', { class: 'resumen-historial', style: 'display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;' }, [
      el('div', { class: 'tarjeta', style: 'padding: 12px; text-align: center;' }, [
        el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Total'),
        el('p', { style: 'margin: 5px 0 0 0; font-size: 20px; font-weight: bold;' }, String(datos.resumen.total || 0)),
      ]),
      el('div', { class: 'tarjeta', style: 'padding: 12px; text-align: center;' }, [
        el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Atendidas'),
        el('p', { style: 'margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: var(--exito);' }, String(datos.resumen.atendida || 0)),
      ]),
      el('div', { class: 'tarjeta', style: 'padding: 12px; text-align: center;' }, [
        el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Canceladas'),
        el('p', { style: 'margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: var(--advertencia);' }, String(datos.resumen.cancelada || 0)),
      ]),
      el('div', { class: 'tarjeta', style: 'padding: 12px; text-align: center;' }, [
        el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Inasistencias'),
        el('p', { style: 'margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: var(--error);' }, String(datos.resumen.inasistencia || 0)),
      ]),
    ])

    const tablaCitas = el('div', { class: 'tabla-contenedor' }, [
      el('table', { class: 'tabla' }, [
        el('thead', {}, el('tr', {}, [
          el('th', {}, 'Fecha'),
          el('th', {}, 'Hora'),
          el('th', {}, 'Médico'),
          el('th', {}, 'Especialidad'),
          el('th', {}, 'Estado'),
          el('th', { class: 'col-acciones' }, 'Acciones'),
        ])),
        el('tbody', { id: 'cu14-tabla-historial' },
          datos.citas.length === 0
            ? el('tr', {}, el('td', { colSpan: '6', class: 'celda-vacia' }, 'No hay citas para este paciente'))
            : null
        ),
      ]),
    ])

    contenido.replaceChildren(vistaPaciente, filtros, resumenDiv, tablaCitas)

    if (datos.citas.length > 0) {
      const tbody = document.getElementById('cu14-tabla-historial')
      tbody.replaceChildren(...datos.citas.map(cita => {
        const btnCambios = el('button', { class: 'btn btn-pequeno', type: 'button' }, 'Ver cambios')
        btnCambios.addEventListener('click', () => mostrarCambios(cita.id_cita))

        const claseEstado = `estado-cita ${cita.estado.toLowerCase()}`
        const horaFormato = cita.hora_inicio.substring(0, 5)
        const fechaFormato = cita.fecha.split('-').reverse().join('/')

        return el('tr', {}, [
          el('td', {}, fechaFormato),
          el('td', {}, horaFormato),
          el('td', {}, cita.medico),
          el('td', {}, cita.especialidad),
          el('td', {}, el('span', { class: claseEstado }, cita.estado)),
          el('td', {}, btnCambios),
        ])
      }))
    }

    const btnVolverElm = document.getElementById('cu14-volver')
    btnVolverElm.onclick = () => mostrarBusqueda(datos)
    document.getElementById('cu14-estado').addEventListener('change', () => {
      datos.filtroEstado = document.getElementById('cu14-estado').value
      cargarHistorial(datos)
    })
    document.getElementById('cu14-desde').addEventListener('change', () => {
      datos.filtroDesde = document.getElementById('cu14-desde').value
      cargarHistorial(datos)
    })
    document.getElementById('cu14-hasta').addEventListener('change', () => {
      datos.filtroHasta = document.getElementById('cu14-hasta').value
      cargarHistorial(datos)
    })
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al cargar historial')
    aviso(msg, 'error')
  }
}

function formatearFechaHora(timestamp) {
  if (!timestamp) return ''
  const partes = timestamp.substring(0, 16).split(' ')
  if (partes.length === 2) {
    const fecha = partes[0].split('-').reverse().join('/')
    const hora = partes[1]
    return `${fecha} ${hora}`
  }
  return timestamp.substring(0, 16)
}

async function mostrarCambios(idCita) {
  try {
    const resp = await obtenerCambiosCita(idCita)
    const cita = resp.cita
    const cambios = resp.cambios || []

    let contenidoModal = [
      el('h3', {}, `Cambios de ${cita.codigo}`),
      el('p', {}, `${cita.paciente} - ${cita.especialidad}`),
      el('p', { class: 'texto-suave' }, `Estado actual: ${cita.estado_actual}`),
    ]

    if (cambios.length === 0) {
      contenidoModal.push(el('p', { class: 'texto-suave' }, 'No hay cambios registrados'))
    } else {
      const tablaCambios = el('table', { class: 'tabla', style: 'table-layout: auto; width: 100%;' }, [
        el('thead', {}, el('tr', {}, [
          el('th', { style: 'text-align: left; width: 120px;' }, 'Fecha'),
          el('th', { style: 'text-align: left; width: 100px;' }, 'Usuario'),
          el('th', { style: 'text-align: left; width: auto; max-width: 150px;' }, 'Cambio'),
          el('th', { style: 'text-align: left; width: auto;' }, 'Detalle'),
        ])),
        el('tbody', {}, cambios.map((cambio, idx) => {
          let cambioTexto = ''
          if (idx === 0 && !cambio.estado_anterior) {
            cambioTexto = 'Creada'
          } else if (cambio.estado_anterior === cambio.estado_nuevo) {
            cambioTexto = 'Reprogramada'
          } else {
            cambioTexto = `${cambio.estado_anterior} → ${cambio.estado_nuevo}`
          }

          const fechaFormato = formatearFechaHora(cambio.fecha)
          return el('tr', {}, [
            el('td', { style: 'font-size: 12px;' }, fechaFormato),
            el('td', { style: 'font-size: 12px;' }, cambio.usuario || 'Sistema'),
            el('td', { style: 'font-size: 12px; word-wrap: break-word;' }, cambioTexto),
            el('td', { style: 'font-size: 12px; word-wrap: break-word;' }, cambio.observacion || '-'),
          ])
        })),
      ])
      contenidoModal.push(tablaCambios)
    }

    const btnCerrar = el('button', { class: 'btn secundario', type: 'button' }, 'Cerrar')
    mostrarModal({
      titulo: 'Cambios de estado de cita',
      contenido: el('div', {}, contenidoModal),
      botones: [btnCerrar],
    })
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al cargar cambios')
    aviso(msg, 'error')
  }
}
