/**
 * CU04 - Gestionar pacientes (paquete personas, Ciclo 1)
 * Pantalla: lista, crear, modificar, desactivar pacientes
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { icono } from '../../../componentes/iconos.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarPacientes, crearPaciente, modificarPaciente, cambiarEstadoPaciente } from '../api.js'

export default function CU04GestionarPacientes(contenedor) {
  PantallaCU04(contenedor)
}

// ---- CU04: inicio ----
function PantallaCU04(contenedor) {
  const datos = { pacientes: [], pacienteEditar: null, pagina: 1, total_paginas: 1, total: 0 }

  const pantalla = el('div', { class: 'cu04' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Gestionar pacientes'),
      el('div', { class: 'acciones' }, [
        el('button', { class: 'btn', id: 'cu04-nuevo' }, 'Nuevo paciente'),
      ]),
    ]),
    crearBuscador(datos),
    crearTabla(datos),
    crearPaginacion(datos),
  ])

  contenedor.append(pantalla)

  document.getElementById('cu04-nuevo').addEventListener('click', () => {
    datos.pacienteEditar = null
    mostrarFormulario(datos)
  })

  cargarPacientes(datos)
}

function crearBuscador(datos) {
  const buscar = el('input', { type: 'text', id: 'cu04-buscar', placeholder: 'Buscar por CI, nombre o apellido...', maxlength: 100 })
  const estado = el('select', { id: 'cu04-estado' }, [
    el('option', { value: 'activo' }, 'Activos'),
    el('option', { value: 'inactivo' }, 'Inactivos'),
    el('option', { value: '' }, 'Todos'),
  ])

  const form = el('form', { class: 'formulario', style: 'margin-bottom: 20px;' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu04-buscar' }, 'Buscar'),
      buscar,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu04-estado' }, 'Estado'),
      estado,
    ]),
  ])

  buscar.addEventListener('keyup', () => { datos.pagina = 1; cargarPacientes(datos) })
  estado.addEventListener('change', () => { datos.pagina = 1; cargarPacientes(datos) })

  return form
}

function crearTabla(datos) {
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'CI'),
      el('th', {}, 'Nombre'),
      el('th', {}, 'Teléfono'),
      el('th', {}, 'Edad'),
      el('th', {}, 'Estado'),
      el('th', { style: 'width: 180px;' }, 'Acciones'),
    ])),
    el('tbody', { id: 'cu04-tabla' }, el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px;' }, 'Cargando...'))),
  ])

  return el('div', { class: 'tabla-contenedor' }, tabla)
}

function crearPaginacion(datos) {
  return el('div', { id: 'cu04-paginacion', style: 'margin-top: 20px; text-align: center; display: flex; justify-content: center; gap: 10px; align-items: center;' })
}

async function cargarPacientes(datos) {
  const buscar = document.getElementById('cu04-buscar')?.value || ''
  const estado = document.getElementById('cu04-estado')?.value || ''

  try {
    const query = `?buscar=${encodeURIComponent(buscar)}&estado=${encodeURIComponent(estado)}&pagina=${datos.pagina}&por_pagina=20`
    const resp = await listarPacientes(query)
    datos.pacientes = resp.pacientes
    datos.total = resp.total
    datos.total_paginas = resp.total_paginas

    const tbody = document.getElementById('cu04-tabla')
    if (!resp.pacientes || resp.pacientes.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px; color: var(--suave);' }, 'No hay pacientes'))
      )
      actualizarPaginacion(datos)
      return
    }

    tbody.replaceChildren(...resp.pacientes.map(pac => {
      const btnEditar = el('button', { class: 'btn secundario', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, 'Editar')
      const btnVerCitas = el('button', { class: 'btn secundario', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, 'Ver citas')
      const btnDesactivar = el('button', { class: 'btn peligro', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, pac.estado === 'activo' ? 'Desactivar' : 'Activar')

      btnEditar.addEventListener('click', () => {
        datos.pacienteEditar = pac
        mostrarFormulario(datos)
      })

      btnVerCitas.addEventListener('click', () => {
        verCitasPaciente(pac.id_paciente, `${pac.nombres} ${pac.apellidos}`)
      })

      btnDesactivar.addEventListener('click', () => {
        const nuevoEstado = pac.estado === 'activo' ? 'inactivo' : 'activo'
        cambiarEstadoPac(pac.id_paciente, nuevoEstado, datos)
      })

      const etiquetaEstado = el('span', {
        class: `estado ${pac.estado === 'activo' ? 'ok' : 'inactivo'}`
      }, pac.estado === 'activo' ? 'Activo' : 'Inactivo')

      const celdaAcciones = el('td', { style: 'display: flex; gap: 8px; align-items: center; flex-wrap: wrap;' }, [btnEditar, btnVerCitas, btnDesactivar])

      const edad = pac.fecha_nacimiento ? Math.floor((new Date() - new Date(pac.fecha_nacimiento)) / (365.25 * 24 * 60 * 60 * 1000)) : '-'

      return el('tr', {}, [
        el('td', {}, pac.ci),
        el('td', {}, `${pac.nombres} ${pac.apellidos}`),
        el('td', {}, pac.telefono || '(sin teléfono)'),
        el('td', {}, `${edad} años`),
        el('td', {}, etiquetaEstado),
        celdaAcciones,
      ])
    }))
    tbody.replaceChildren(...resp.pacientes.map(pac => {
      const btnEditar = el('button', { class: 'btn secundario', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, 'Editar')
      const btnVerCitas = el('button', { class: 'btn secundario', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, 'Ver citas')
      const btnDesactivar = el('button', { class: 'btn peligro', type: 'button', style: 'font-size: 12px; padding: 8px 12px;' }, pac.estado === 'activo' ? 'Desactivar' : 'Activar')

      btnEditar.addEventListener('click', () => {
        datos.pacienteEditar = pac
        mostrarFormulario(datos)
      })

      btnVerCitas.addEventListener('click', () => verCitasPaciente(pac.id_paciente, `${pac.nombres} ${pac.apellidos}`))
      btnDesactivar.addEventListener('click', () => cambiarEstadoPac(pac.id_paciente, pac.estado === 'activo' ? 'inactivo' : 'activo', datos))

      const etiquetaEstado = el('span', { class: `estado ${pac.estado === 'activo' ? 'ok' : 'inactivo'}` }, pac.estado === 'activo' ? 'Activo' : 'Inactivo')
      const edad = pac.edad || '-'
      const celdaAcciones = el('td', { style: 'display: flex; gap: 8px; align-items: center; flex-wrap: wrap;' }, [btnEditar, btnVerCitas, btnDesactivar])

      return el('tr', {}, [
        el('td', {}, pac.ci),
        el('td', {}, `${pac.nombres} ${pac.apellidos}`),
        el('td', {}, pac.telefono || '(sin teléfono)'),
        el('td', {}, `${edad} años`),
        el('td', {}, etiquetaEstado),
        celdaAcciones,
      ])
    }))
    actualizarPaginacion(datos)
  } catch (error) {
    const tbody = document.getElementById('cu04-tabla')
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px; color: var(--error);' }, `Error: ${error.message}`))
    )
  }
}

function actualizarPaginacion(datos) {
  const pag = document.getElementById('cu04-paginacion')
  pag.replaceChildren()

  const btnAnterior = el('button', { class: 'btn secundario', disabled: datos.pagina === 1 }, 'Anterior')
  const btnSiguiente = el('button', { class: 'btn secundario', disabled: datos.pagina === datos.total_paginas }, 'Siguiente')
  const txtInfo = el('span', { style: 'font-size: 14px; min-width: 180px;' }, `Página ${datos.pagina} de ${datos.total_paginas} (${datos.total} total)`)

  btnAnterior.addEventListener('click', () => {
    if (datos.pagina > 1) { datos.pagina--; cargarPacientes(datos) }
  })
  btnSiguiente.addEventListener('click', () => {
    if (datos.pagina < datos.total_paginas) { datos.pagina++; cargarPacientes(datos) }
  })

  pag.append(btnAnterior, txtInfo, btnSiguiente)
}

function mostrarFormulario(datos) {
  const titulo = datos.pacienteEditar ? 'Editar paciente' : 'Nuevo paciente'
  const ci = el('input', { type: 'text', placeholder: 'CI (números)', maxlength: 10, value: datos.pacienteEditar?.ci || '', disabled: !!datos.pacienteEditar })
  const nombres = el('input', { type: 'text', placeholder: 'Nombres', maxlength: 100, value: datos.pacienteEditar?.nombres || '' })
  const apellidos = el('input', { type: 'text', placeholder: 'Apellidos', maxlength: 100, value: datos.pacienteEditar?.apellidos || '' })
  const fechaNac = el('input', { type: 'date', value: datos.pacienteEditar?.fecha_nacimiento || '' })
  const sexo = el('select', {}, [
    el('option', { value: '' }, '-- Seleccionar --'),
    el('option', { value: 'M', selected: datos.pacienteEditar?.sexo === 'M' }, 'Masculino'),
    el('option', { value: 'F', selected: datos.pacienteEditar?.sexo === 'F' }, 'Femenino'),
    el('option', { value: 'O', selected: datos.pacienteEditar?.sexo === 'O' }, 'Otro'),
  ])
  const telefono = el('input', { type: 'tel', placeholder: 'Teléfono', maxlength: 15, value: datos.pacienteEditar?.telefono || '' })
  const correo = el('input', { type: 'email', placeholder: 'Correo (opcional)', maxlength: 100, value: datos.pacienteEditar?.correo || '' })
  const direccion = el('input', { type: 'text', placeholder: 'Dirección (opcional)', maxlength: 200, value: datos.pacienteEditar?.direccion || '' })
  const tipo_sangre = el('select', {}, [
    el('option', { value: '' }, '-- Seleccionar --'),
    el('option', { value: 'O+', selected: datos.pacienteEditar?.tipo_sangre === 'O+' }, 'O+'),
    el('option', { value: 'O-', selected: datos.pacienteEditar?.tipo_sangre === 'O-' }, 'O-'),
    el('option', { value: 'A+', selected: datos.pacienteEditar?.tipo_sangre === 'A+' }, 'A+'),
    el('option', { value: 'A-', selected: datos.pacienteEditar?.tipo_sangre === 'A-' }, 'A-'),
    el('option', { value: 'B+', selected: datos.pacienteEditar?.tipo_sangre === 'B+' }, 'B+'),
    el('option', { value: 'B-', selected: datos.pacienteEditar?.tipo_sangre === 'B-' }, 'B-'),
    el('option', { value: 'AB+', selected: datos.pacienteEditar?.tipo_sangre === 'AB+' }, 'AB+'),
    el('option', { value: 'AB-', selected: datos.pacienteEditar?.tipo_sangre === 'AB-' }, 'AB-'),
  ])
  const alergias = el('textarea', { placeholder: 'Alergias (opcional)', maxlength: 500, rows: 2, value: datos.pacienteEditar?.alergias || '' })
  const enfermedad_cronica = el('textarea', { placeholder: 'Enfermedad crónica (opcional)', maxlength: 500, rows: 2, value: datos.pacienteEditar?.enfermedad_cronica || '' })
  const antecedentes = el('textarea', { placeholder: 'Antecedentes (opcional)', maxlength: 500, rows: 2, value: datos.pacienteEditar?.antecedentes || '' })

  const formulario = el('form', { class: 'formulario' }, [
    el('fieldset', {}, [
      el('legend', {}, 'Datos personales'),
      el('div', { class: 'campo' }, [el('label', {}, 'CI'), ci]),
      el('div', { class: 'campo' }, [el('label', {}, 'Nombres'), nombres]),
      el('div', { class: 'campo' }, [el('label', {}, 'Apellidos'), apellidos]),
      el('div', { class: 'campo' }, [el('label', {}, 'Fecha de nacimiento'), fechaNac]),
      el('div', { class: 'campo' }, [el('label', {}, 'Sexo'), sexo]),
    ]),
    el('fieldset', {}, [
      el('legend', {}, 'Contacto'),
      el('div', { class: 'campo' }, [el('label', {}, 'Teléfono'), telefono]),
      el('div', { class: 'campo' }, [el('label', {}, 'Correo'), correo]),
      el('div', { class: 'campo' }, [el('label', {}, 'Dirección'), direccion]),
    ]),
    el('fieldset', {}, [
      el('legend', {}, 'Información médica'),
      el('div', { class: 'campo' }, [el('label', {}, 'Tipo de sangre'), tipo_sangre]),
      el('div', { class: 'campo' }, [el('label', {}, 'Alergias'), alergias]),
      el('div', { class: 'campo' }, [el('label', {}, 'Enfermedad crónica'), enfermedad_cronica]),
      el('div', { class: 'campo' }, [el('label', {}, 'Antecedentes'), antecedentes]),
    ]),
  ])

  const btnGuardar = el('button', { type: 'button', class: 'btn' }, 'Guardar')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo,
    contenido: formulario,
    botones: [btnGuardar, btnCancelar],
    primerFoco: ci
  })

  btnGuardar.addEventListener('click', () => guardarPaciente(
    {
      ci: ci.value,
      nombres: nombres.value,
      apellidos: apellidos.value,
      fecha_nacimiento: fechaNac.value,
      sexo: sexo.value,
      telefono: telefono.value,
      correo: correo.value,
      direccion: direccion.value,
      tipo_sangre: tipo_sangre.value,
      alergias: alergias.value,
      enfermedad_cronica: enfermedad_cronica.value,
      antecedentes: antecedentes.value,
    },
    datos,
    cerrar
  ))
  btnCancelar.addEventListener('click', cerrar)
}

async function guardarPaciente(datosForm, datos, cerrar) {
  try {
    if (!datosForm.ci.trim() || !datosForm.nombres.trim() || !datosForm.apellidos.trim() || !datosForm.fecha_nacimiento || !datosForm.telefono.trim()) {
      aviso('Todos los campos requeridos deben ser completados.', 'error')
      return
    }

    const payload = {
      ci: datosForm.ci.trim(),
      nombres: datosForm.nombres.trim(),
      apellidos: datosForm.apellidos.trim(),
      fecha_nacimiento: datosForm.fecha_nacimiento,
      sexo: datosForm.sexo || null,
      telefono: datosForm.telefono.trim(),
      correo: datosForm.correo.trim() || null,
      direccion: datosForm.direccion.trim() || null,
      tipo_sangre: datosForm.tipo_sangre || null,
      alergias: datosForm.alergias.trim() || null,
      enfermedad_cronica: datosForm.enfermedad_cronica.trim() || null,
      antecedentes: datosForm.antecedentes.trim() || null,
    }

    if (datos.pacienteEditar) {
      await modificarPaciente(datos.pacienteEditar.id_paciente, payload)
      aviso('Paciente actualizado.', 'ok')
    } else {
      await crearPaciente(payload)
      aviso('Paciente registrado.', 'ok')
    }

    cerrar()
    cargarPacientes(datos)
  } catch (error) {
    const mensaje = error instanceof ErrorApi ? error.message : 'Error al guardar'
    aviso(mensaje, 'error')
  }
}

async function cambiarEstadoPac(id, nuevoEstado, datos) {
  const btnConfirmar = el('button', { type: 'button', class: 'btn peligro' }, 'Confirmar')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo: 'Confirmar cambio de estado',
    contenido: `¿Cambiar estado a ${nuevoEstado}?`,
    botones: [btnConfirmar, btnCancelar]
  })

  btnConfirmar.addEventListener('click', async () => {
    cerrar()
    try {
      await cambiarEstadoPaciente(id, nuevoEstado)
      aviso(`Estado actualizado a ${nuevoEstado}.`, 'ok')
      cargarPacientes(datos)
    } catch (error) {
      const mensaje = error instanceof ErrorApi ? error.message : 'Error al cambiar estado'
      aviso(mensaje, 'error')
    }
  })

  btnCancelar.addEventListener('click', cerrar)
}

async function verCitasPaciente(id, nombrePaciente) {
  try {
    const resp = await fetch(`/clinica-vida-salud/backend/public/api/v1/personas/pacientes/${id}/citas`, { credentials: 'include' })
    if (!resp.ok) {
      const error = await resp.json()
      throw new Error(error.error?.mensaje || 'Error al cargar citas')
    }
    const { citas } = await resp.json()

    const tabla = el('table', { class: 'tabla', style: 'width: 100%;' }, [
      el('thead', {}, el('tr', {}, [
        el('th', {}, 'Código'),
        el('th', {}, 'Fecha'),
        el('th', {}, 'Horario'),
        el('th', {}, 'Médico'),
        el('th', {}, 'Especialidad'),
        el('th', {}, 'Estado'),
      ])),
      el('tbody', {}, citas.length === 0 ? el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px;' }, 'Sin citas registradas')) : citas.map(c => {
        const fecha = new Date(c.fecha).toLocaleDateString('es-ES')
        const hora = c.hora_inicio.slice(0, 5)
        return el('tr', {}, [
          el('td', {}, c.codigo),
          el('td', {}, fecha),
          el('td', {}, hora),
          el('td', {}, `${c.medico_nombres} ${c.medico_apellidos}`),
          el('td', {}, c.especialidad),
          el('td', {}, el('span', { class: `estado estado-${c.estado}` }, c.estado)),
        ])
      })),
    ])

    const btnCerrar = el('button', { type: 'button', class: 'btn' }, 'Cerrar')
    const { cerrar } = mostrarModal({
      titulo: `Citas de ${nombrePaciente}`,
      contenido: tabla,
      botones: [btnCerrar]
    })
    btnCerrar.addEventListener('click', cerrar)
  } catch (error) {
    aviso(error.message, 'error')
  }
}
// ---- CU04: fin ----
