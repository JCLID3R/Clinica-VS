/**
 * CU06 - Gestionar especialidades (paquete personas, Ciclo 1)
 * Pantalla: lista, crear, modificar, desactivar especialidades
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { icono } from '../../../componentes/iconos.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarEspecialidades, crearEspecialidad, modificarEspecialidad, cambiarEstadoEspecialidad } from '../api.js'

export default function CU06GestionarEspecialidades(contenedor) {
  PantallaCU06(contenedor)
}

// ---- CU06: inicio ----
function PantallaCU06(contenedor) {
  const datos = { especialidades: [], especialidadEditar: null }

  const pantalla = el('div', { class: 'cu06' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Gestionar especialidades'),
      el('div', { class: 'acciones' }, [
        el('button', { class: 'btn', id: 'cu06-nuevo' }, 'Nueva especialidad'),
      ]),
    ]),
    crearBuscador(datos),
    crearTabla(datos),
  ])

  contenedor.append(pantalla)

  document.getElementById('cu06-nuevo').addEventListener('click', () => {
    datos.especialidadEditar = null
    mostrarFormulario(datos)
  })

  cargarEspecialidades(datos)
}

function crearBuscador(datos) {
  const filtro = el('input', { type: 'text', id: 'cu06-filtro', placeholder: 'Buscar especialidad...', maxlength: 100 })
  const estado = el('select', { id: 'cu06-estado' }, [
    el('option', { value: '' }, 'Todos'),
    el('option', { value: 'activo' }, 'Activo'),
    el('option', { value: 'inactivo' }, 'Inactivo'),
  ])

  const form = el('form', { class: 'formulario', style: 'margin-bottom: 20px;' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu06-filtro' }, 'Buscar'),
      filtro,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu06-estado' }, 'Estado'),
      estado,
    ]),
  ])

  filtro.addEventListener('keyup', () => cargarEspecialidades(datos))
  estado.addEventListener('change', () => cargarEspecialidades(datos))

  return form
}

function crearTabla(datos) {
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Nombre'),
      el('th', {}, 'Descripción'),
      el('th', {}, 'Médicos'),
      el('th', {}, 'Estado'),
      el('th', { style: 'width: 120px;' }, 'Acciones'),
    ])),
    el('tbody', { id: 'cu06-tabla' }, el('tr', {}, el('td', { colSpan: '5', style: 'text-align: center; padding: 20px;' }, 'Cargando...'))),
  ])

  return el('div', { class: 'tabla-contenedor' }, tabla)
}

async function cargarEspecialidades(datos) {
  const filtro = document.getElementById('cu06-filtro')?.value || ''
  const estado = document.getElementById('cu06-estado')?.value || ''

  try {
    const resp = await listarEspecialidades(filtro, estado)
    datos.especialidades = resp.especialidades

    const tbody = document.getElementById('cu06-tabla')
    if (!resp.especialidades || resp.especialidades.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '5', style: 'text-align: center; padding: 20px; color: var(--suave);' }, 'No hay especialidades'))
      )
      return
    }

    tbody.replaceChildren(...resp.especialidades.map(esp => {
      const btnEditar = el('button', { class: 'btn secundario', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, 'Editar')
      const btnDesactivar = el('button', { class: 'btn peligro', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, esp.estado === 'activo' ? 'Desactivar' : 'Activar')

      btnEditar.addEventListener('click', () => {
        datos.especialidadEditar = esp
        mostrarFormulario(datos)
      })

      btnDesactivar.addEventListener('click', () => {
        const nuevoEstado = esp.estado === 'activo' ? 'inactivo' : 'activo'
        cambiarEstadoEsp(esp.id_especialidad, nuevoEstado, esp, datos)
      })

      const etiquetaEstado = el('span', {
        class: 'estado',
        style: `padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; ${esp.estado === 'activo' ? 'background: #CCFBF1; color: #134E4A;' : 'background: #F3F4F6; color: #5B6B69;'}`
      }, esp.estado === 'activo' ? 'Activo' : 'Inactivo')

      const celdaAcciones = el('td', { style: 'display: flex; gap: 8px; align-items: center;' }, [btnEditar, btnDesactivar])

      return el('tr', {}, [
        el('td', {}, esp.nombre),
        el('td', { style: 'max-width: 200px; white-space: normal;' }, esp.descripcion || '(sin descripción)'),
        el('td', {}, esp.cantidad_medicos || 0),
        el('td', {}, etiquetaEstado),
        celdaAcciones,
      ])
    }))
  } catch (error) {
    const tbody = document.getElementById('cu06-tabla')
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '5', style: 'text-align: center; padding: 20px; color: var(--error);' }, `Error: ${error.message}`))
    )
  }
}

function mostrarFormulario(datos) {
  const titulo = datos.especialidadEditar ? 'Editar especialidad' : 'Nueva especialidad'
  const nombre = el('input', {
    type: 'text',
    placeholder: 'Nombre (3-60 caracteres)',
    maxlength: 60,
    value: datos.especialidadEditar?.nombre || ''
  })
  const descripcion = el('textarea', {
    placeholder: 'Descripción (opcional, máx. 200 caracteres)',
    maxlength: 200,
    rows: 3,
    value: datos.especialidadEditar?.descripcion || ''
  })

  const formulario = el('form', { class: 'formulario' }, [
    el('div', { class: 'campo' }, [
      el('label', {}, 'Nombre'),
      nombre,
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Descripción'),
      descripcion,
    ]),
  ])

  const btnGuardar = el('button', { type: 'button', class: 'btn' }, 'Guardar')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo,
    contenido: formulario,
    botones: [btnGuardar, btnCancelar],
    primerFoco: nombre
  })

  btnGuardar.addEventListener('click', () => guardarEspecialidad(nombre.value, descripcion.value, datos, cerrar))
  btnCancelar.addEventListener('click', cerrar)
}

async function guardarEspecialidad(nombre, descripcion, datos, cerrar) {
  try {
    if (!nombre.trim()) {
      aviso('El nombre es requerido.', 'error')
      return
    }

    const datosForm = { nombre: nombre.trim(), descripcion: descripcion.trim() || null }

    if (datos.especialidadEditar) {
      await modificarEspecialidad(datos.especialidadEditar.id_especialidad, datosForm)
      aviso('Especialidad actualizada.', 'ok')
    } else {
      await crearEspecialidad(datosForm)
      aviso('Especialidad creada.', 'ok')
    }

    cerrar()
    cargarEspecialidades(datos)
  } catch (error) {
    const mensaje = error instanceof ErrorApi ? error.message : 'Error al guardar'
    aviso(mensaje, 'error')
  }
}

async function cambiarEstadoEsp(id, nuevoEstado, esp, datos) {
  // Si intenta desactivar y hay médicos activos, pedir confirmación con modal
  if (nuevoEstado === 'inactivo' && esp.cantidad_medicos > 0) {
    const btnConfirmar = el('button', { type: 'button', class: 'btn peligro' }, 'Desactivar')
    const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

    const { cerrar } = mostrarModal({
      titulo: 'Confirmar desactivación',
      contenido: `Hay ${esp.cantidad_medicos} médicos con esta especialidad. ¿Desactivar de todas formas?`,
      botones: [btnConfirmar, btnCancelar]
    })

    btnConfirmar.addEventListener('click', async () => {
      cerrar()
      ejecutarCambioEstado()
    })
    btnCancelar.addEventListener('click', cerrar)
    return
  }

  ejecutarCambioEstado()

  async function ejecutarCambioEstado() {
    try {
      await cambiarEstadoEspecialidad(id, nuevoEstado)
      aviso(`Estado actualizado a ${nuevoEstado}.`, 'ok')
      cargarEspecialidades(datos)
    } catch (error) {
      const mensaje = error instanceof ErrorApi ? error.message : 'Error al cambiar estado'
      aviso(mensaje, 'error')
    }
  }
}
// ---- CU06: fin ----
