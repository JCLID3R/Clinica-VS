/**
 * CU13 - Consultar agenda médica  (paquete citas, Ciclo 2)
 * Actores: Médico, Recepcionista, Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { obtenerAgendaMedica } from '../api.js'
import { listarEspecialidadesActivas } from '../../personas/api.js'
import { listarMedicosPorEspecialidad } from '../../agenda/api.js'
import { obtenerUsuario } from '../../../nucleo/auth/sesion.js'

export default function CU13ConsultarAgendaMedica(contenedor) {
  PantallaCU13(contenedor)
}

function PantallaCU13(contenedor) {
  const datos = {
    especialidades: [],
    citas: [],
    fecha: obtenerLunes(new Date()),
    idMedico: null,
    idEspecialidad: null,
    esMedico: false,
    nombreMedico: '',
  }

  // Detectar rol desde sesión
  const usuario = obtenerUsuario()
  datos.esMedico = usuario && usuario.rol === 'Medico'
  datos.nombreMedico = usuario ? (usuario.username || 'Dr. Médico') : 'Dr. Médico'

  const selectEsp = el('select', { id: 'cu13-especialidad' }, [
    el('option', { value: '' }, 'Seleccionar especialidad...'),
  ])

  const selectMed = el('select', { id: 'cu13-medico', disabled: true }, [
    el('option', { value: '' }, 'Seleccionar médico...'),
  ])

  const filtrosForm = el('form', { class: 'formulario', id: 'cu13-filtros', style: 'margin: 20px 0;' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu13-especialidad' }, 'Especialidad'),
      selectEsp,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu13-medico' }, 'Médico'),
      selectMed,
    ]),
  ])

  const titulo = datos.esMedico
    ? `Agenda de: ${datos.nombreMedico}`
    : 'Agenda médica'

  const pantalla = el('div', { class: 'cu13' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, titulo),
      el('div', { class: 'controles-agenda' }, [
        el('button', { class: 'btn secundario', id: 'cu13-anterior' }, 'Semana anterior'),
        el('button', { class: 'btn secundario', id: 'cu13-hoy' }, 'Hoy'),
        el('button', { class: 'btn secundario', id: 'cu13-siguiente' }, 'Semana siguiente'),
      ]),
    ]),
    ...(datos.esMedico ? [] : [filtrosForm]),
    el('div', { id: 'cu13-calendario', style: 'margin-top: 20px;' }),
  ])

  contenedor.append(pantalla)

  document.getElementById('cu13-anterior').addEventListener('click', () => {
    datos.fecha = new Date(datos.fecha.getTime() - 7 * 24 * 60 * 60 * 1000)
    if (datos.esMedico || datos.idMedico) cargarAgenda(datos)
  })

  document.getElementById('cu13-hoy').addEventListener('click', () => {
    datos.fecha = obtenerLunes(new Date())
    if (datos.esMedico || datos.idMedico) cargarAgenda(datos)
  })

  document.getElementById('cu13-siguiente').addEventListener('click', () => {
    datos.fecha = new Date(datos.fecha.getTime() + 7 * 24 * 60 * 60 * 1000)
    if (datos.esMedico || datos.idMedico) cargarAgenda(datos)
  })

  if (!datos.esMedico) {
    selectEsp.addEventListener('change', async () => {
      datos.idEspecialidad = selectEsp.value ? parseInt(selectEsp.value) : null
      datos.idMedico = null
      selectMed.value = ''
      selectMed.disabled = true
      selectMed.replaceChildren(el('option', { value: '' }, 'Seleccionar médico...'))

      if (datos.idEspecialidad) {
        try {
          const resp = await listarMedicosPorEspecialidad(datos.idEspecialidad)
          selectMed.disabled = false
          selectMed.replaceChildren(
            el('option', { value: '' }, 'Seleccionar médico...'),
            ...(resp.medicos || []).map(m => el('option', { value: m.id_medico }, m.nombres + ' ' + m.apellidos))
          )
        } catch (err) {
          const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al cargar médicos')
          aviso(msg, 'error')
        }
      }
      mostrarCalendario(datos)
    })

    selectMed.addEventListener('change', () => {
      datos.idMedico = selectMed.value ? parseInt(selectMed.value) : null
      if (datos.idMedico) {
        cargarAgenda(datos)
      } else {
        mostrarCalendario(datos)
      }
    })
  }

  if (datos.esMedico) {
    inicializarMedico(datos)
  } else {
    inicializar(datos, selectEsp)
  }
}

function actualizarTitulo(datos) {
  const h1 = document.querySelector('.cu13 h1')
  if (h1) {
    h1.textContent = `Agenda de: ${datos.nombreMedico}`
  }
}

function obtenerLunes(fecha) {
  const d = new Date(fecha)
  const dia = d.getDay()
  const diff = d.getDate() - dia + (dia === 0 ? -6 : 1)
  return new Date(d.setDate(diff))
}

function formatearFecha(d) {
  const año = d.getFullYear()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const día = String(d.getDate()).padStart(2, '0')
  return `${año}-${mes}-${día}`
}

function formatearFechaLocal(d) {
  const año = d.getFullYear()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const día = String(d.getDate()).padStart(2, '0')
  return `${día}/${mes}/${año}`
}

async function inicializarMedico(datos) {
  cargarAgenda(datos)
}

async function inicializar(datos, selectEsp) {
  try {
    const respEsp = await listarEspecialidadesActivas()
    datos.especialidades = respEsp.especialidades || []
    selectEsp.replaceChildren(
      el('option', { value: '' }, 'Seleccionar especialidad...'),
      ...datos.especialidades.map(e => el('option', { value: e.id_especialidad }, e.nombre))
    )
    mostrarCalendario(datos)
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al cargar especialidades')
    aviso(msg, 'error')
  }
}

async function cargarAgenda(datos) {
  if (!datos.esMedico && !datos.idMedico) {
    mostrarCalendario(datos)
    return
  }

  try {
    const hasta = new Date(datos.fecha.getTime() + 6 * 24 * 60 * 60 * 1000)
    const query = new URLSearchParams()
    if (!datos.esMedico && datos.idMedico) {
      query.append('id_medico', datos.idMedico)
    }
    query.append('desde', formatearFecha(datos.fecha))
    query.append('hasta', formatearFecha(hasta))

    const resp = await obtenerAgendaMedica('?' + query.toString())
    datos.citas = resp.citas || []
    if (datos.citas.length > 0 && datos.citas[0].nombre_medico) {
      datos.nombreMedico = datos.citas[0].nombre_medico
      actualizarTitulo(datos)
    }
    mostrarCalendario(datos)
  } catch (err) {
    const msg = err instanceof ErrorApi ? err.mensaje : (err.message || 'Error al cargar agenda')
    aviso(msg, 'error')
  }
}

function mostrarCalendario(datos) {
  const contenedor = document.getElementById('cu13-calendario')

  if (!datos.esMedico && !datos.idMedico) {
    contenedor.replaceChildren(
      el('p', { class: 'texto-suave', style: 'text-align: center; font-size: 14px;' },
        'Seleccione un médico para ver su agenda')
    )
    return
  }

  const hasta = new Date(datos.fecha.getTime() + 6 * 24 * 60 * 60 * 1000)
  const rango = el('p', { style: 'font-size: 14px; margin-bottom: 15px; color: var(--suave);' },
    `Semana del ${formatearFechaLocal(datos.fecha)} al ${formatearFechaLocal(hasta)}`
  )

  contenedor.replaceChildren(rango, crearVistaSemanal(datos))
}

function crearVistaSemanal(datos) {
  const grid = el('div', { class: 'grid-citas-semanal', style: 'display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; overflow-x: auto; padding: 10px 0;' })

  const citasPorFecha = {}
  datos.citas.forEach(c => {
    if (!citasPorFecha[c.fecha]) citasPorFecha[c.fecha] = []
    citasPorFecha[c.fecha].push(c)
  })

  const dias = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM']
  const colores = ['--exito', '--exito', '--exito', '--exito', '--exito', '--advertencia', '--advertencia']

  for (let i = 0; i < 7; i++) {
    const fecha = new Date(datos.fecha)
    fecha.setDate(fecha.getDate() + i)
    const fechaStr = formatearFecha(fecha)
    const fechaLocal = formatearFechaLocal(fecha)

    const citasDia = citasPorFecha[fechaStr] || []
    const colorBorde = `var(${colores[i]})`

    const col = el('div', { class: 'dia-columna', style: `border: 1px solid var(--borde); border-radius: 6px; padding: 12px; background-color: #f9f9f9; min-height: 300px; display: flex; flex-direction: column;` }, [
      el('div', { class: 'dia-header', style: `border-bottom: 2px solid ${colorBorde}; padding-bottom: 10px; margin-bottom: 12px;` }, [
        el('strong', { style: 'display: block; font-size: 13px; color: #333;' }, dias[i]),
        el('span', { class: 'dia-fecha', style: 'display: block; font-size: 11px; color: var(--suave); margin-top: 2px;' }, fechaLocal),
      ]),
      el('div', { class: 'dia-cuerpo', style: 'flex: 1; overflow-y: auto;' },
        citasDia.length === 0
          ? el('p', { style: 'text-align: center; font-size: 12px; color: var(--suave); margin: 20px 0;' }, 'Sin citas')
          : el('div', { style: 'display: flex; flex-direction: column; gap: 8px;' },
              citasDia.sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio))
                .map(c => {
                  const claseEstado = `estado-cita ${c.estado.toLowerCase()}`
                  return el('div', { class: 'cita-card', style: 'background: white; padding: 8px; border-radius: 4px; border-left: 3px solid var(--info); font-size: 11px; line-height: 1.4;' }, [
                    el('div', { style: 'font-weight: bold; color: #333; margin-bottom: 3px;' }, `${c.hora_inicio.substring(0, 5)} - ${c.hora_fin.substring(0, 5)}`),
                    el('p', { style: 'margin: 2px 0; font-weight: 500; color: #333; word-break: break-word;' }, c.paciente),
                    el('p', { style: 'margin: 2px 0; color: var(--suave); font-size: 10px;' }, c.especialidad),
                    el('span', { class: claseEstado, style: 'display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; margin-top: 3px;' }, c.estado),
                  ])
                })
            )
      ),
    ])

    grid.append(col)
  }

  return grid
}
