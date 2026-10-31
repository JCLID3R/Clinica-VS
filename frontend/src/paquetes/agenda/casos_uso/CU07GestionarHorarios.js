/**
 * CU07 - Gestionar horarios de atención  (paquete agenda, Ciclo 1)
 * Actores: Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarMedicos } from '../../personas/api.js'
import { listarHorariosPorMedico, crearHorario, modificarHorario, cambiarEstadoHorario } from '../api.js'

export default function CU07GestionarHorarios(contenedor) {
  PantallaCU07(contenedor)
}

const DIAS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']

// ---- CU07: inicio ----
function PantallaCU07(contenedor) {
  const urlParams = new URLSearchParams(window.location.hash.split('?')[1])
  const idMedicoParam = urlParams.get('medico') ? parseInt(urlParams.get('medico')) : null

  const datos = {
    medicos: [],
    medicoSeleccionado: null,
    horarios: [],
    horarioEditar: null,
  }

  const pantalla = el('div', { class: 'cu07' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Gestionar horarios de atención'),
    ]),
  ])

  contenedor.append(pantalla)

  if (idMedicoParam) {
    cargarMedicoDirecto(datos, pantalla, idMedicoParam)
  } else {
    mostrarSelectorMedicos(datos, pantalla)
  }
}

function mostrarSelectorMedicos(datos, pantalla) {
  const contenido = pantalla.querySelector('.encabezado-pagina').parentElement
  contenido.innerHTML = ''
  contenido.append(
    el('div', { class: 'encabezado-pagina' }, el('h1', {}, 'Gestionar horarios de atención')),
    el('div', { style: 'padding: 20px;' }, [
      el('form', { class: 'formulario' }, [
        el('div', { class: 'campo' }, [
          el('label', {}, 'Médico'),
          el('input', { type: 'text', id: 'cu07-buscar-medico', placeholder: 'Buscar médico...', maxlength: 100 }),
        ]),
      ]),
      el('div', { id: 'cu07-lista-medicos', style: 'margin-top: 20px;' }),
    ])
  )

  const inputBuscar = pantalla.querySelector('#cu07-buscar-medico')
  inputBuscar.addEventListener('input', () => buscarMedicos(datos, pantalla))
  buscarMedicos(datos, pantalla)
}

async function buscarMedicos(datos, pantalla) {
  const buscar = pantalla.querySelector('#cu07-buscar-medico')?.value || ''

  try {
    const query = `?buscar=${encodeURIComponent(buscar)}&estado=activo&pagina=1&por_pagina=50`
    const resp = await listarMedicos(query)
    datos.medicos = resp.medicos

    const lista = pantalla.querySelector('#cu07-lista-medicos')
    if (datos.medicos.length === 0) {
      lista.replaceChildren(el('p', { style: 'color: var(--suave);' }, 'No hay médicos activos'))
      return
    }

    lista.replaceChildren(
      el('div', { style: 'display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;' }, datos.medicos.map(med => {
        const tarjeta = el('div', { style: 'padding: 15px; border: 1px solid var(--borde); border-radius: 4px; cursor: pointer; transition: all 0.2s;' }, [
          el('div', { style: 'font-weight: bold; margin-bottom: 5px;' }, `${med.nombres} ${med.apellidos}`),
          el('small', { style: 'color: var(--suave); display: block; margin-bottom: 8px;' }, med.registro_profesional),
        ])

        const esp = med.especialidades_nombres || ''
        if (esp) {
          const especialidades = esp.replace(/^{|}$/g, '').split(',').map(e => e.replace(/^"(.*)"$/, '$1').trim()).filter(e => e)
          tarjeta.append(el('div', {}, especialidades.map(e => el('span', { class: 'etiqueta', style: 'display: inline-block; padding: 4px 8px; margin-right: 4px; margin-bottom: 8px; font-size: 12px; background: #E0F2FE; color: #0369A1; border-radius: 4px;' }, e))))
        }

        tarjeta.style.cursor = 'pointer'
        tarjeta.addEventListener('mouseover', () => tarjeta.style.backgroundColor = 'var(--fondo-secundario)')
        tarjeta.addEventListener('mouseout', () => tarjeta.style.backgroundColor = 'transparent')
        tarjeta.addEventListener('click', () => {
          datos.medicoSeleccionado = med
          window.location.hash = `#/agenda/horarios?medico=${med.id_medico}`
          cargarMedicoDirecto(datos, pantalla, med.id_medico)
        })

        return tarjeta
      }))
    )
  } catch (error) {
    aviso(`Error al cargar médicos: ${error.message}`, 'error')
  }
}

async function cargarMedicoDirecto(datos, pantalla, idMedico) {
  try {
    const resp = await listarHorariosPorMedico(idMedico)
    datos.medicoSeleccionado = resp.medico
    datos.horarios = resp.horarios

    const medNombre = `${datos.medicoSeleccionado.nombres} ${datos.medicoSeleccionado.apellidos}`
    const medReg = datos.medicoSeleccionado.registro_profesional
    const medEspecialidades = (datos.medicoSeleccionado.especialidades || []).map(e => e.nombre)

    const contenido = pantalla.parentElement
    contenido.innerHTML = ''
    contenido.append(pantalla)

    const btnVolver = el('button', { class: 'btn secundario' }, 'Volver a médicos')
    const btnNuevo = el('button', { class: 'btn' }, 'Agregar bloque')

    const encabezado = pantalla.querySelector('.encabezado-pagina')
    encabezado.replaceChildren(
      el('h1', {}, 'Gestionar horarios de atención'),
      el('div', { class: 'acciones' }, [btnVolver, btnNuevo])
    )

    const tarjetaMedico = el('div', { style: 'margin: 20px 0; padding: 15px; background: var(--fondo-secundario); border-radius: 4px;' }, [
      el('div', { style: 'font-weight: bold; font-size: 18px; margin-bottom: 5px;' }, medNombre),
      el('small', { style: 'color: var(--suave); display: block; margin-bottom: 10px;' }, medReg),
      el('div', { style: 'display: flex; gap: 8px; flex-wrap: wrap;' }, medEspecialidades.map(esp => el('span', { class: 'etiqueta', style: 'padding: 4px 8px; font-size: 12px; background: #E0F2FE; color: #0369A1; border-radius: 4px;' }, esp))),
    ])

    pantalla.replaceChildren(
      encabezado,
      tarjetaMedico,
      el('div', { id: 'cu07-tabla-contenedor' })
    )

    crearTablaHorarios(datos, pantalla)

    btnVolver.addEventListener('click', () => {
      window.location.hash = '#/agenda/horarios'
    })

    btnNuevo.addEventListener('click', () => {
      datos.horarioEditar = null
      mostrarModalHorario(datos)
    })

  } catch (error) {
    aviso(`Error al cargar horarios: ${error.message}`, 'error')
  }
}

function crearTablaHorarios(datos, pantalla) {
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Día'),
      el('th', {}, 'Turno'),
      el('th', {}, 'Horario'),
      el('th', {}, 'Duración'),
      el('th', {}, 'Fichas'),
      el('th', {}, 'Estado'),
      el('th', { style: 'width: 180px;' }, 'Acciones'),
    ])),
    el('tbody', { id: 'cu07-tabla' }),
  ])

  const cuerpo = tabla.querySelector('tbody')
  if (datos.horarios.length === 0) {
    cuerpo.append(el('tr', {}, el('td', { colSpan: '7', style: 'text-align: center; padding: 20px; color: var(--suave);' }, 'Sin horarios registrados')))
  } else {
    cuerpo.append(...datos.horarios.map(hor => crearFilaHorario(hor, datos)))
  }

  const contenedor = pantalla.querySelector('#cu07-tabla-contenedor')
  contenedor.replaceChildren(el('div', { class: 'tabla-contenedor' }, tabla))
}

function crearFilaHorario(hor, datos) {
  const [hiHora, hiMin] = hor.hora_inicio.split(':').map(Number)
  const [hfHora, hfMin] = hor.hora_fin.split(':').map(Number)
  const turno = hiHora < 12 ? 'Mañana' : 'Tarde'

  const minTotal = (hfHora * 60 + hfMin) - (hiHora * 60 + hiMin)
  const fichas = minTotal / hor.duracion_cita

  const btnEditar = el('button', { class: 'btn secundario', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, 'Editar')
  const btnDesactivar = el('button', {
    class: hor.estado === 'activo' ? 'btn peligro' : 'btn ok',
    type: 'button',
    style: 'font-size: 12px; padding: 8px 12px;'
  }, hor.estado === 'activo' ? 'Desactivar' : 'Activar')

  btnEditar.addEventListener('click', () => {
    datos.horarioEditar = hor
    mostrarModalHorario(datos)
  })

  btnDesactivar.addEventListener('click', () => {
    const nuevoEstado = hor.estado === 'activo' ? 'inactivo' : 'activo'
    cambiarEstadoBloque(hor.id_horario, nuevoEstado, datos)
  })

  const etiquetaEstado = el('span', {
    class: `estado ${hor.estado === 'activo' ? 'ok' : 'inactivo'}`
  }, hor.estado === 'activo' ? 'Activo' : 'Inactivo')

  const celdaAcciones = el('td', { style: 'display: flex; gap: 8px; align-items: center; flex-wrap: wrap;' }, [btnEditar, btnDesactivar])

  return el('tr', {}, [
    el('td', {}, DIAS[hor.dia_semana - 1]),
    el('td', {}, turno),
    el('td', {}, `${hor.hora_inicio.substring(0, 5)} - ${hor.hora_fin.substring(0, 5)}`),
    el('td', {}, `${hor.duracion_cita} min`),
    el('td', {}, fichas),
    el('td', {}, etiquetaEstado),
    celdaAcciones,
  ])
}

function mostrarModalHorario(datos) {
  const titulo = datos.horarioEditar ? 'Editar bloque de horario' : 'Agregar bloque de horario'

  const horaInicio = el('input', { type: 'time', value: datos.horarioEditar?.hora_inicio?.substring(0, 5) || '' })
  const horaFin = el('input', { type: 'time', value: datos.horarioEditar?.hora_fin?.substring(0, 5) || '' })
  const diaSemana = el('select', {}, [
    el('option', { value: '', selected: !datos.horarioEditar }, '-- Seleccionar día --'),
    ...DIAS.map((dia, idx) => el('option', {
      value: idx + 1,
      selected: datos.horarioEditar?.dia_semana === idx + 1
    }, dia))
  ])
  const duracion = el('select', {}, [
    el('option', { value: '', selected: !datos.horarioEditar }, '-- Seleccionar duración --'),
    el('option', { value: '15', selected: datos.horarioEditar?.duracion_cita === 15 }, '15 minutos'),
    el('option', { value: '20', selected: datos.horarioEditar?.duracion_cita === 20 }, '20 minutos'),
    el('option', { value: '30', selected: datos.horarioEditar?.duracion_cita === 30 }, '30 minutos'),
    el('option', { value: '45', selected: datos.horarioEditar?.duracion_cita === 45 }, '45 minutos'),
    el('option', { value: '60', selected: datos.horarioEditar?.duracion_cita === 60 }, '60 minutos'),
  ])

  const ayuda = el('div', { style: 'margin-top: 15px; padding: 10px; background: var(--fondo-secundario); border-radius: 4px; font-size: 14px; color: var(--suave);' })
  const actualizarAyuda = () => {
    const hi = horaInicio.value
    const hf = horaFin.value
    const dur = parseInt(duracion.value)

    if (!hi || !hf || !dur) {
      ayuda.textContent = ''
      return
    }

    const [hiHora, hiMin] = hi.split(':').map(Number)
    const [hfHora, hfMin] = hf.split(':').map(Number)
    const turno = hiHora < 12 ? 'Mañana' : 'Tarde'
    const minTotal = (hfHora * 60 + hfMin) - (hiHora * 60 + hiMin)

    if (minTotal <= 0) {
      ayuda.innerHTML = '<span style="color: var(--error);">⚠ La hora de fin debe ser posterior a la de inicio</span>'
      return
    }

    if (minTotal % dur !== 0) {
      const fichas = Math.floor(minTotal / dur)
      const sobran = minTotal % dur
      ayuda.innerHTML = `<span style="color: var(--error);">⚠ El rango no es divisible: ${fichas} fichas + ${sobran} minutos sobrantes</span>`
    } else {
      const fichas = minTotal / dur
      ayuda.textContent = `Turno: ${turno} · ${fichas} ficha${fichas !== 1 ? 's' : ''}`
    }
  }

  horaInicio.addEventListener('change', actualizarAyuda)
  horaFin.addEventListener('change', actualizarAyuda)
  duracion.addEventListener('change', actualizarAyuda)

  const formulario = el('form', { class: 'formulario' }, [
    el('div', { style: 'display: grid; grid-template-columns: 1fr 1fr; gap: 20px;' }, [
      el('div', { class: 'campo' }, [el('label', {}, 'Hora de inicio'), horaInicio]),
      el('div', { class: 'campo' }, [el('label', {}, 'Hora de fin'), horaFin]),
    ]),
    el('div', { style: 'display: grid; grid-template-columns: 1fr 1fr; gap: 20px;' }, [
      el('div', { class: 'campo' }, [el('label', {}, 'Día de la semana'), diaSemana]),
      el('div', { class: 'campo' }, [el('label', {}, 'Duración de cita'), duracion]),
    ]),
    ayuda,
  ])

  const btnGuardar = el('button', { type: 'submit', class: 'btn' }, datos.horarioEditar ? 'Guardar cambios' : 'Crear bloque')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  formulario.append(el('div', { style: 'display: flex; gap: 10px; margin-top: 20px;' }, [btnGuardar, btnCancelar]))

  btnCancelar.addEventListener('click', () => cerrarModal())
  formulario.addEventListener('submit', async (e) => {
    e.preventDefault()
    await guardarHorario(datos, {
      dia_semana: parseInt(diaSemana.value),
      hora_inicio: horaInicio.value,
      hora_fin: horaFin.value,
      duracion_cita: parseInt(duracion.value),
    })
    cerrarModal()
  })

  mostrarModal({ titulo, contenido: formulario })
  actualizarAyuda()
}

async function guardarHorario(datos, valores) {
  try {
    if (datos.horarioEditar) {
      await modificarHorario(datos.horarioEditar.id_horario, valores)
      aviso('Bloque modificado correctamente')
    } else {
      await crearHorario(datos.medicoSeleccionado.id_medico, valores)
      aviso('Bloque creado correctamente')
    }
    await cargarMedicoDirecto(datos, document.querySelector('.cu07'), datos.medicoSeleccionado.id_medico)
  } catch (error) {
    if (error instanceof ErrorApi && error.estado === 409 && error.detalle?.advertencia) {
      mostrarAdvertenciaCitas(error.detalle.citas, datos, error.detalle.idHorario)
    } else {
      aviso(`Error: ${error.message}`, 'error')
    }
  }
}

async function cambiarEstadoBloque(idHorario, nuevoEstado, datos) {
  try {
    await cambiarEstadoHorario(idHorario, { estado: nuevoEstado })
    await cargarMedicoDirecto(datos, document.querySelector('.cu07'), datos.medicoSeleccionado.id_medico)
    aviso(`Estado actualizado a ${nuevoEstado}`)
  } catch (error) {
    if (error instanceof ErrorApi && error.estado === 409 && error.detalle?.advertencia) {
      mostrarAdvertenciaCitas(error.detalle.citas, datos, idHorario, nuevoEstado)
    } else {
      aviso(`Error: ${error.message}`, 'error')
    }
  }
}

function mostrarAdvertenciaCitas(citas, datos, idHorario, nuevoEstado = 'inactivo') {
  const listaCitas = el('div', { style: 'max-height: 300px; overflow-y: auto; margin: 10px 0; border: 1px solid var(--borde); border-radius: 4px;' })

  citas.forEach(cita => {
    listaCitas.append(el('div', { style: 'padding: 10px; border-bottom: 1px solid var(--borde); last-child: border-bottom: none;' }, [
      el('strong', {}, `${cita.paciente_nombres} ${cita.paciente_apellidos}`),
      el('br'),
      el('small', { style: 'color: var(--suave);' }, `${cita.fecha} ${cita.hora_inicio.substring(0, 5)} - ${cita.hora_fin.substring(0, 5)} | ${cita.especialidad} | ${cita.estado}`),
    ]))
  })

  const btnConfirmar = el('button', { class: 'btn peligro', type: 'button' }, `${nuevoEstado === 'inactivo' ? 'Desactivar' : 'Activar'} de todas formas`)
  const btnCancelar = el('button', { class: 'btn secundario', type: 'button' }, 'Cancelar')

  const modal = el('div', {}, [
    el('p', { style: 'margin-bottom: 10px;' }, 'Este bloque tiene citas futuras:'),
    listaCitas,
    el('div', { style: 'display: flex; gap: 10px; margin-top: 20px;' }, [btnConfirmar, btnCancelar]),
  ])

  btnCancelar.addEventListener('click', () => cerrarModal())
  btnConfirmar.addEventListener('click', async () => {
    cerrarModal()
    await cambiarEstadoConConfirmacion(idHorario, nuevoEstado, datos)
  })

  mostrarModal({ titulo: 'Advertencia: citas futuras', contenido: modal })
}

async function cambiarEstadoConConfirmacion(idHorario, estado, datos) {
  try {
    await cambiarEstadoHorario(idHorario, { estado, confirmar: true })
    await cargarMedicoDirecto(datos, document.querySelector('.cu07'), datos.medicoSeleccionado.id_medico)
    aviso(`Estado actualizado a ${estado}`)
  } catch (error) {
    aviso(`Error: ${error.message}`, 'error')
  }
}

function cerrarModal() {
  const modal = document.querySelector('[role="dialog"]')
  if (modal) modal.remove()
  const overlay = document.querySelector('.modal-overlay')
  if (overlay) overlay.remove()
}
// ---- CU07: fin ----
