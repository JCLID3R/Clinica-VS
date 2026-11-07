/**
 * CU15 - Generar reportes  (paquete reportes, Ciclo 2)
 * Actores: Administrador, Recepcionista
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { generarReporte, descargarCsv } from '../api.js'
import { listarMedicos } from '../../personas/api.js'
import { listarEspecialidades } from '../../personas/api.js'

export default function CU15GenerarReportes(contenedor) {
  PantallaCU15(contenedor)
}

function PantallaCU15(contenedor) {
  const datos = {
    tipo: 'estados',
    desde: obtenerPrimerDiaDelMes(),
    hasta: new Date().toISOString().split('T')[0],
    idMedico: null,
    idEspecialidad: null,
    medicos: [],
    especialidades: [],
    resultado: null,
  }

  const pantalla = el('div', { class: 'cu15' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Generar reportes'),
    ]),
    crearFiltros(datos),
    el('div', { id: 'cu15-resultado' }),
  ])

  contenedor.append(pantalla)
  cargarSelects(datos)
}

function obtenerPrimerDiaDelMes() {
  const hoy = new Date()
  return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-01`
}

function crearFiltros(datos) {
  const selectTipo = el('select', { id: 'cu15-tipo' }, [
    el('option', { value: 'estados' }, 'Por estados de cita'),
    el('option', { value: 'especialidades' }, 'Por especialidades'),
    el('option', { value: 'medicos' }, 'Por médicos'),
    el('option', { value: 'asistencia' }, 'De asistencia'),
    el('option', { value: 'ocupacion' }, 'De ocupación'),
  ])

  const inputDesde = el('input', { type: 'date', id: 'cu15-desde', value: datos.desde })
  const inputHasta = el('input', { type: 'date', id: 'cu15-hasta', value: datos.hasta })

  const selectMedico = el('select', { id: 'cu15-medico' }, [
    el('option', { value: '' }, 'Todos los médicos'),
  ])

  const selectEspecialidad = el('select', { id: 'cu15-especialidad' }, [
    el('option', { value: '' }, 'Todas las especialidades'),
  ])

  const btnGenerar = el('button', { class: 'btn', id: 'cu15-generar' }, 'Generar reporte')
  const btnDescargarCsv = el('button', { class: 'btn secundario', id: 'cu15-csv', disabled: true }, 'Descargar CSV')
  const btnImprimir = el('button', { class: 'btn secundario', id: 'cu15-imprimir', disabled: true }, 'Imprimir')

  const form = el('form', { class: 'formulario-filtros' }, [
    el('div', { class: 'campo' }, [
      el('label', {}, 'Tipo de reporte'),
      selectTipo,
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Desde'),
      inputDesde,
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Hasta'),
      inputHasta,
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Médico'),
      selectMedico,
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Especialidad'),
      selectEspecialidad,
    ]),
    el('div', { class: 'acciones' }, [
      btnGenerar,
      btnDescargarCsv,
      btnImprimir,
    ]),
  ])

  selectTipo.addEventListener('change', () => {
    datos.tipo = selectTipo.value
    datos.idMedico = null
    datos.idEspecialidad = null
    selectMedico.value = ''
    selectEspecialidad.value = ''
  })

  inputDesde.addEventListener('change', () => {
    datos.desde = inputDesde.value
  })

  inputHasta.addEventListener('change', () => {
    datos.hasta = inputHasta.value
  })

  selectMedico.addEventListener('change', () => {
    datos.idMedico = selectMedico.value ? parseInt(selectMedico.value) : null
  })

  selectEspecialidad.addEventListener('change', () => {
    datos.idEspecialidad = selectEspecialidad.value ? parseInt(selectEspecialidad.value) : null
  })

  btnGenerar.addEventListener('click', async (e) => {
    e.preventDefault()
    await cargarReporte(datos)
    btnDescargarCsv.disabled = false
    btnImprimir.disabled = false
  })

  btnDescargarCsv.addEventListener('click', async () => {
    if (!datos.resultado) return
    try {
      const query = new URLSearchParams()
      query.append('desde', datos.desde)
      query.append('hasta', datos.hasta)
      if (datos.idMedico) query.append('id_medico', datos.idMedico)
      if (datos.idEspecialidad) query.append('id_especialidad', datos.idEspecialidad)

      const baseUrl = window.location.origin + '/clinica-vida-salud/backend/public/api/v1'
      const resp = await fetch(`${baseUrl}/reportes/${datos.tipo}/csv?${query.toString()}`, { credentials: 'include' })
      if (!resp.ok) throw new ErrorApi(resp.status, 'Error al descargar CSV')

      const blob = await resp.blob()
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `reporte_${datos.tipo}_${datos.desde}_${datos.hasta}.csv`
      link.click()
      URL.revokeObjectURL(url)
      aviso('CSV descargado exitosamente', 'ok')
    } catch (err) {
      const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al descargar CSV')
      aviso(msg, 'error')
    }
  })

  btnImprimir.addEventListener('click', () => {
    window.print()
  })

  datos.selectMedico = selectMedico
  datos.selectEspecialidad = selectEspecialidad

  return form
}

async function cargarSelects(datos) {
  try {
    const respMedicos = await listarMedicos()
    datos.medicos = respMedicos.medicos || []

    const respEspecialidades = await listarEspecialidades()
    datos.especialidades = respEspecialidades.especialidades || []

    if (datos.selectMedico) {
      datos.selectMedico.replaceChildren(
        el('option', { value: '' }, 'Todos los médicos'),
        ...datos.medicos.map(m => el('option', { value: m.id_medico }, m.nombres + ' ' + m.apellidos))
      )
    }

    if (datos.selectEspecialidad) {
      datos.selectEspecialidad.replaceChildren(
        el('option', { value: '' }, 'Todas las especialidades'),
        ...datos.especialidades.map(e => el('option', { value: e.id_especialidad }, e.nombre))
      )
    }
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al cargar opciones')
    aviso(msg, 'error')
  }
}

async function cargarReporte(datos) {
  const resultado = document.getElementById('cu15-resultado')

  try {
    const query = new URLSearchParams()
    query.append('desde', datos.desde)
    query.append('hasta', datos.hasta)
    if (datos.idMedico) query.append('id_medico', datos.idMedico)
    if (datos.idEspecialidad) query.append('id_especialidad', datos.idEspecialidad)

    const resp = await generarReporte(datos.tipo, '?' + query.toString())
    datos.resultado = resp

    let resumen = resp.resumen || {}
    const datos_list = resp.datos || []

    // Si es reporte por estados y no hay resumen, calcular desde datos
    if (datos.tipo === 'estados' && Object.keys(resumen).length === 0 && datos_list.length > 0) {
      resumen.total = datos_list.reduce((sum, row) => sum + (row.total || 0), 0)
      resumen.atendidas = datos_list.reduce((sum, row) => sum + (row.atendidas || 0), 0)
      resumen.canceladas = datos_list.reduce((sum, row) => sum + (row.canceladas || 0), 0)
      resumen.inasistencias = datos_list.reduce((sum, row) => sum + (row.inasistencias || 0), 0)
      const divisor = (resumen.atendidas || 0) + (resumen.inasistencias || 0)
      resumen.porcentaje_asistencia = divisor > 0 ? parseFloat(((resumen.atendidas / divisor) * 100).toFixed(1)) : 0
    }

    // Formatear fechas para el subtítulo
    const [desdeY, desdeM, desdeD] = datos.desde.split('-')
    const desdeFormato = `${desdeD}/${desdeM}/${desdeY}`
    const [hastaY, hastaM, hastaD] = datos.hasta.split('-')
    const hastaFormato = `${hastaD}/${hastaM}/${hastaY}`

    const mapaTipos = {
      'estados': 'por estados de cita',
      'especialidades': 'por especialidades',
      'medicos': 'por médicos',
      'asistencia': 'de asistencia',
      'ocupacion': 'de ocupación'
    }

    // Crear resumen como tarjetas (5 columnas)
    const resumenDiv = crearResumenTarjetas(resumen, datos.tipo)

    // Calcular máximo para gráficos
    const maximo = obtenerMaximoDelDataset(datos_list, datos.tipo)

    const tabla = el('table', { class: 'tabla' }, [
      el('thead', {}, crearEncabezados(datos.tipo, datos_list)),
      el('tbody', {}, datos_list.length === 0
        ? el('tr', {}, el('td', { colSpan: '10', class: 'celda-vacia' }, 'No hay datos para este reporte'))
        : datos_list.map(row => crearFilaTabla(row, datos.tipo, maximo))
      ),
    ])

    const contenido = [
      el('h3', {}, 'Resultado'),
      el('p', { style: 'color: var(--suave); margin: 10px 0 20px 0; font-size: 14px;' },
        `Reporte ${mapaTipos[datos.tipo]} — del ${desdeFormato} al ${hastaFormato}`
      ),
    ]
    if (resumenDiv) contenido.push(resumenDiv)
    contenido.push(el('div', { class: 'tabla-contenedor' }, tabla))

    resultado.replaceChildren(...contenido)
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al generar reporte')
    aviso(msg, 'error')
    resultado.replaceChildren()
  }
}

function crearResumenTarjetas(resumen, tipo) {
  const tarjetas = []

  if (resumen.total !== undefined) {
    tarjetas.push(el('div', { class: 'tarjeta', style: 'padding: 15px; text-align: center;' }, [
      el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Total'),
      el('p', { style: 'margin: 8px 0 0 0; font-size: 24px; font-weight: bold;' }, String(resumen.total || 0)),
    ]))
  }

  if (resumen.atendidas !== undefined) {
    tarjetas.push(el('div', { class: 'tarjeta', style: 'padding: 15px; text-align: center;' }, [
      el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Atendidas'),
      el('p', { style: 'margin: 8px 0 0 0; font-size: 24px; font-weight: bold; color: var(--ok);' }, String(resumen.atendidas || 0)),
    ]))
  }

  if (resumen.canceladas !== undefined) {
    tarjetas.push(el('div', { class: 'tarjeta', style: 'padding: 15px; text-align: center;' }, [
      el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Canceladas'),
      el('p', { style: 'margin: 8px 0 0 0; font-size: 24px; font-weight: bold; color: var(--advertencia);' }, String(resumen.canceladas || 0)),
    ]))
  }

  if (resumen.inasistencias !== undefined) {
    tarjetas.push(el('div', { class: 'tarjeta', style: 'padding: 15px; text-align: center;' }, [
      el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, 'Inasistencias'),
      el('p', { style: 'margin: 8px 0 0 0; font-size: 24px; font-weight: bold; color: var(--error);' }, String(resumen.inasistencias || 0)),
    ]))
  }

  // Calcular % Asistencia si hay atendidas e inasistencias
  let porcentajeAsistencia = resumen.porcentaje_asistencia
  if (porcentajeAsistencia === undefined && resumen.atendidas !== undefined && resumen.inasistencias !== undefined) {
    const divisor = (resumen.atendidas || 0) + (resumen.inasistencias || 0)
    porcentajeAsistencia = divisor > 0 ? parseFloat(((resumen.atendidas / divisor) * 100).toFixed(1)) : 0
  }

  if (porcentajeAsistencia !== undefined) {
    tarjetas.push(el('div', { class: 'tarjeta', style: 'padding: 15px; text-align: center;' }, [
      el('p', { style: 'margin: 0; font-size: 12px; color: var(--suave);' }, '% Asistencia'),
      el('p', { style: 'margin: 8px 0 0 0; font-size: 24px; font-weight: bold; color: ' + (porcentajeAsistencia > 0 ? 'var(--ok)' : 'var(--suave)') + ';' },
        porcentajeAsistencia > 0 ? `${porcentajeAsistencia}%` : '—'
      ),
    ]))
  }

  if (tarjetas.length === 0) return null

  return el('div', { style: 'display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px;' }, tarjetas)
}

function crearEncabezados(tipo, datos) {
  if (datos.length === 0) return el('tr', {})

  const primeraFila = datos[0]
  let encabezados = Object.keys(primeraFila)

  const tiposConGraficos = ['estados', 'especialidades', 'medicos']
  if (tiposConGraficos.includes(tipo) && !encabezados.includes('grafico')) {
    encabezados.push('grafico')
  }

  const mapeoEncabezados = {
    'estado': 'Estado',
    'total': 'Total',
    'atendidas': 'Atendidas',
    'canceladas': 'Canceladas',
    'inasistencias': 'Inasistencias',
    'porcentaje_asistencia': '% Asistencia',
    'nombre': 'Nombre',
    'especialidad': 'Especialidad',
    'medico': 'Médico',
    'codigo': 'Código',
    'fecha': 'Fecha',
    'grafico': 'Gráfico',
    'hora_inicio': 'Hora',
    'paciente': 'Paciente',
    'pendientes': 'Pendientes',
    'grafico': 'Gráfico'
  }

  return el('tr', {}, encabezados.map(encabezado => {
    const etiqueta = mapeoEncabezados[encabezado] ||
      encabezado.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase())
    return el('th', {}, etiqueta)
  }))
}

function crearFilaTabla(row, tipo, maximo) {
  const celdas = []
  const tiposConGraficos = ['estados', 'especialidades', 'medicos']

  Object.entries(row).forEach(([key, valor]) => {
    let contenido = valor

    // Formatear fechas
    if (key === 'fecha' && valor) {
      const [año, mes, día] = valor.split('-')
      contenido = `${día}/${mes}/${año}`
    }

    // Formatear horas
    if (key === 'hora_inicio' && valor) {
      contenido = valor.substring(0, 5)
    }

    celdas.push(el('td', {}, contenido))
  })

  // Agregar barra gráfica al final si aplica
  if (tiposConGraficos.includes(tipo) && row.total && maximo > 0) {
    const valor = Number(row.total) || 0
    const ancho = maximo > 0 ? (valor / maximo) * 100 : 0
    const barra = el('div', { style: `display: block; width: ${ancho}%; height: 10px; background: var(--ok); border-radius: 3px;` })
    const contenedor = el('div', { style: 'width: 100%; height: 10px; background: #e8e8e8; border-radius: 3px; display: flex;' }, barra)
    celdas.push(el('td', { style: 'text-align: center; padding: 8px;' }, contenedor))
  }

  return el('tr', {}, celdas)
}

function obtenerMaximoDelDataset(datos, tipo) {
  if (datos.length === 0) return 1

  let maximo = 0
  datos.forEach(row => {
    const valor = Number(row.total) || 0
    if (valor > maximo) {
      maximo = valor
    }
  })

  return Math.max(maximo, 1)
}

// Estilos para impresión
window.addEventListener('DOMContentLoaded', () => {
  const estilo = document.createElement('style')
  estilo.textContent = `
    @media print {
      body {
        page-break-after: auto;
      }
      #menu-lateral, .cabecera, .formulario-filtros, .acciones, .btn {
        display: none !important;
      }
      .tabla {
        font-size: 11px;
      }
    }
  `
  document.head.append(estilo)
})
