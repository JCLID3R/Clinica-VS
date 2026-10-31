/**
 * CU05 - Gestionar médicos (paquete personas, Ciclo 1)
 * Pantalla: lista, crear, modificar, desactivar médicos con especialidades
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { mostrarModal } from '../../../componentes/modal.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarMedicos, crearMedico, modificarMedico, cambiarEstadoMedico, listarEspecialidadesActivas } from '../api.js'

export default function CU05GestionarMedicos(contenedor) {
  PantallaCU05(contenedor)
}

// ---- CU05: inicio ----
function PantallaCU05(contenedor) {
  const datos = { medicos: [], medicoEditar: null, especialidades: [], pagina: 1, total_paginas: 1, total: 0 }

  const pantalla = el('div', { class: 'cu05' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Gestionar médicos'),
      el('div', { class: 'acciones' }, [
        el('button', { class: 'btn', id: 'cu05-nuevo' }, 'Nuevo médico'),
      ]),
    ]),
    crearBuscador(datos),
    crearTabla(datos),
    crearPaginacion(datos),
  ])

  contenedor.append(pantalla)

  document.getElementById('cu05-nuevo').addEventListener('click', () => {
    datos.medicoEditar = null
    mostrarFormulario(datos)
  })

  cargarEspecialidades(datos)
  cargarMedicos(datos)
}

function crearBuscador(datos) {
  const buscar = el('input', { type: 'text', id: 'cu05-buscar', placeholder: 'Buscar por nombre o registro...', maxlength: 100 })
  const estado = el('select', { id: 'cu05-estado' }, [
    el('option', { value: 'activo' }, 'Activos'),
    el('option', { value: 'inactivo' }, 'Inactivos'),
    el('option', { value: '' }, 'Todos'),
  ])

  const form = el('form', { class: 'formulario', style: 'margin-bottom: 20px;' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu05-buscar' }, 'Buscar'),
      buscar,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu05-estado' }, 'Estado'),
      estado,
    ]),
  ])

  let timeoutBusca = null
  buscar.addEventListener('input', () => {
    clearTimeout(timeoutBusca)
    timeoutBusca = setTimeout(() => { datos.pagina = 1; cargarMedicos(datos) }, 300)
  })
  estado.addEventListener('change', () => { datos.pagina = 1; cargarMedicos(datos) })

  return form
}

function crearTabla(datos) {
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Nombre'),
      el('th', {}, 'Registro'),
      el('th', {}, 'Especialidades'),
      el('th', {}, 'Estado'),
      el('th', { style: 'width: 200px;' }, 'Acciones'),
    ])),
    el('tbody', { id: 'cu05-tabla' }, el('tr', {}, el('td', { colSpan: '5', style: 'text-align: center; padding: 20px;' }, 'Cargando...'))),
  ])

  return el('div', { class: 'tabla-contenedor' }, tabla)
}

function crearPaginacion(datos) {
  return el('div', { id: 'cu05-paginacion', style: 'margin-top: 20px; text-align: center; display: flex; justify-content: center; gap: 10px; align-items: center;' })
}

async function cargarEspecialidades(datos) {
  try {
    const resp = await listarEspecialidadesActivas()
    datos.especialidades = resp.especialidades || []
  } catch (error) {
    const msg = error instanceof ErrorApi ? (error.mensaje || error.message) : 'Error al cargar especialidades'
    console.error('Error cargando especialidades:', msg)
  }
}

async function cargarMedicos(datos) {
  const buscar = document.getElementById('cu05-buscar')?.value || ''
  const estado = document.getElementById('cu05-estado')?.value || 'activo'

  try {
    const query = `?buscar=${encodeURIComponent(buscar)}&estado=${encodeURIComponent(estado)}&pagina=${datos.pagina}&por_pagina=20`
    const resp = await listarMedicos(query)
    datos.medicos = resp.medicos
    datos.total = resp.total
    datos.total_paginas = resp.total_paginas

    const tbody = document.getElementById('cu05-tabla')
    if (!resp.medicos || resp.medicos.length === 0) {
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: '5', style: 'text-align: center; padding: 20px; color: var(--suave);' }, 'No hay médicos'))
      )
      actualizarPaginacion(datos)
      return
    }

    tbody.replaceChildren(...resp.medicos.map(med => {
      const botones = []
      const btnEditar = el('button', { class: 'btn', type: 'button', style: 'font-size: 11px; padding: 6px 10px;' }, 'Editar')
      botones.push(btnEditar)

      const btnHorarios = el('button', { class: 'btn', type: 'button', style: 'font-size: 11px; padding: 6px 10px;' }, 'Horarios')
      botones.push(btnHorarios)

      const claseEstado = med.estado === 'activo' ? 'btn peligro' : 'btn ok'
      const btnEstado = el('button', { class: claseEstado, type: 'button', style: 'font-size: 11px; padding: 6px 10px;' }, med.estado === 'activo' ? 'Desactivar' : 'Activar')
      botones.push(btnEstado)

      btnEditar.addEventListener('click', () => {
        datos.medicoEditar = med
        mostrarFormulario(datos)
      })

      btnHorarios.addEventListener('click', () => {
        window.location.hash = `#/agenda/horarios?medico=${med.id_medico}`
      })

      btnEstado.addEventListener('click', () => {
        const nuevoEstado = med.estado === 'activo' ? 'inactivo' : 'activo'
        cambiarEstadoMed(med.id_medico, nuevoEstado, med, datos)
      })

      const etiquetaEstado = el('span', { class: `estado ${med.estado === 'activo' ? 'ok' : 'inactivo'}` }, med.estado === 'activo' ? 'Activo' : 'Inactivo')

      let especialidades = med.especialidades_nombres || []
      if (typeof especialidades === 'string') {
        especialidades = especialidades.replace(/^{|}$/g, '').split(',').map(e => e.replace(/^"(.*)"$/, '$1').trim()).filter(e => e)
      } else if (!Array.isArray(especialidades)) {
        especialidades = []
      }

      const etiquetasEsp = especialidades.map(esp =>
        el('span', { class: 'etiqueta etiqueta-especialidad', style: 'display: inline-block; padding: 4px 8px; margin-right: 4px; font-size: 12px; background: #E0F2FE; color: #0369A1; border-radius: 4px;' }, esp)
      )

      const celdaAcciones = el('td', { style: 'display: flex; gap: 6px; align-items: center; flex-wrap: wrap;' }, botones)

      return el('tr', {}, [
        el('td', {}, `${med.nombres} ${med.apellidos}`),
        el('td', {}, el('code', {}, med.registro_profesional)),
        el('td', {}, etiquetasEsp),
        el('td', {}, etiquetaEstado),
        celdaAcciones,
      ])
    }))
    actualizarPaginacion(datos)
  } catch (error) {
    const tbody = document.getElementById('cu05-tabla')
    const msg = error instanceof ErrorApi ? error.mensaje : error.message
    tbody.replaceChildren(
      el('tr', {}, el('td', { colSpan: '5', style: 'text-align: center; padding: 20px; color: var(--error);' }, `Error: ${msg}`))
    )
  }
}

function actualizarPaginacion(datos) {
  const pag = document.getElementById('cu05-paginacion')
  pag.replaceChildren()

  const btnAnterior = el('button', { class: 'btn secundario', disabled: datos.pagina === 1 }, 'Anterior')
  const btnSiguiente = el('button', { class: 'btn secundario', disabled: datos.pagina === datos.total_paginas }, 'Siguiente')
  const txtInfo = el('span', { style: 'font-size: 14px; min-width: 180px;' }, `Página ${datos.pagina} de ${datos.total_paginas} (${datos.total} total)`)

  btnAnterior.addEventListener('click', () => {
    if (datos.pagina > 1) { datos.pagina--; cargarMedicos(datos) }
  })
  btnSiguiente.addEventListener('click', () => {
    if (datos.pagina < datos.total_paginas) { datos.pagina++; cargarMedicos(datos) }
  })

  pag.append(btnAnterior, txtInfo, btnSiguiente)
}

function mostrarFormulario(datos) {
  const es_nuevo = !datos.medicoEditar
  const titulo = es_nuevo ? 'Nuevo médico' : 'Editar médico'
  const medico = datos.medicoEditar || {}

  const registro = el('input', { type: 'text', placeholder: 'MP-12345', maxlength: 10, value: medico.registro_profesional || '', disabled: !es_nuevo })
  const nombres = el('input', { type: 'text', placeholder: 'Nombres', maxlength: 100, value: medico.nombres || '' })
  const apellidos = el('input', { type: 'text', placeholder: 'Apellidos', maxlength: 100, value: medico.apellidos || '' })
  const telefono = el('input', { type: 'tel', placeholder: 'Teléfono (opcional)', maxlength: 20, value: medico.telefono || '' })
  const correo = el('input', { type: 'email', placeholder: 'Correo (opcional)', maxlength: 100, value: medico.correo || '' })

  let idsSeleccionados = medico.especialidades_ids || []
  if (typeof idsSeleccionados === 'string') {
    idsSeleccionados = idsSeleccionados.replace(/^{|}$/g, '').split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
  } else if (!Array.isArray(idsSeleccionados)) {
    idsSeleccionados = []
  }

  const divEspecialidades = el('div', { style: 'display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-top: 8px;' },
    datos.especialidades.map(e => {
      const checked = idsSeleccionados.includes(e.id_especialidad)
      return el('label', { style: 'display: flex; align-items: center; gap: 6px; white-space: nowrap;' }, [
        el('input', { type: 'checkbox', id: `esp-${e.id_especialidad}`, value: e.id_especialidad, checked }),
        el('span', {}, e.nombre),
      ])
    })
  )

  const checkCrearUsuario = el('input', { type: 'checkbox', id: 'frm-crear-usuario' })
  const divUsuario = el('div', { style: 'display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;' }, [
    el('h4', {}, 'Crear cuenta de usuario'),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Usuario'),
      el('input', { type: 'text', id: 'frm-username', placeholder: 'nombre_usuario', maxlength: 40 }),
    ]),
    el('div', { class: 'campo' }, [
      el('label', {}, 'Correo'),
      el('input', { type: 'email', id: 'frm-usuario-correo', placeholder: 'correo@ejemplo.com', maxlength: 100 }),
    ]),
  ])

  checkCrearUsuario.addEventListener('change', () => {
    divUsuario.style.display = checkCrearUsuario.checked ? 'block' : 'none'
  })

  const formulario = el('form', { class: 'formulario' }, [
    el('div', { class: 'campo' }, [el('label', {}, 'Registro profesional *'), registro]),
    el('div', { style: 'display: grid; grid-template-columns: 1fr 1fr; gap: 10px;' }, [
      el('div', { class: 'campo' }, [el('label', {}, 'Nombres *'), nombres]),
      el('div', { class: 'campo' }, [el('label', {}, 'Apellidos *'), apellidos]),
    ]),
    el('div', { style: 'display: grid; grid-template-columns: 1fr 1fr; gap: 10px;' }, [
      el('div', { class: 'campo' }, [el('label', {}, 'Teléfono'), telefono]),
      el('div', { class: 'campo' }, [el('label', {}, 'Correo'), correo]),
    ]),
    el('div', { class: 'campo' }, [el('label', {}, 'Especialidades *'), divEspecialidades]),
    el('div', { class: 'campo', style: 'flex-direction: row; align-items: center;' }, [
      el('label', { style: 'display: flex; align-items: center; gap: 6px; margin: 0;' }, [
        checkCrearUsuario,
        el('span', {}, 'Crear cuenta de usuario (rol Médico)'),
      ]),
    ]),
    divUsuario,
  ])

  const btnGuardar = el('button', { type: 'button', class: 'btn' }, es_nuevo ? 'Registrar' : 'Actualizar')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo,
    contenido: formulario,
    botones: [btnGuardar, btnCancelar],
    primerFoco: registro
  })

  btnGuardar.addEventListener('click', () => guardarMedico(
    {
      registro_profesional: registro.value.trim(),
      nombres: nombres.value.trim(),
      apellidos: apellidos.value.trim(),
      telefono: telefono.value.trim() || null,
      correo: correo.value.trim() || null,
      especialidades: Array.from(document.querySelectorAll('input[id^="esp-"]:checked')).map(cb => parseInt(cb.value)),
      crear_usuario: checkCrearUsuario.checked,
      usuario: checkCrearUsuario.checked ? {
        username: document.getElementById('frm-username')?.value.trim() || '',
        correo: document.getElementById('frm-usuario-correo')?.value.trim() || '',
        generar_contrasena: true,
      } : null,
    },
    datos,
    cerrar
  ))
  btnCancelar.addEventListener('click', cerrar)
}

async function guardarMedico(datosForm, datos, cerrar) {
  try {
    if (!datosForm.registro_profesional || !datosForm.nombres || !datosForm.apellidos || datosForm.especialidades.length === 0) {
      aviso('Todos los campos requeridos deben ser completados.', 'error')
      return
    }

    if (datosForm.crear_usuario && (!datosForm.usuario.username || !datosForm.usuario.correo)) {
      aviso('Usuario y correo son requeridos para crear cuenta.', 'error')
      return
    }

    if (datos.medicoEditar) {
      await modificarMedico(datos.medicoEditar.id_medico, datosForm)
      aviso('Médico actualizado.', 'ok')
    } else {
      await crearMedico(datosForm)
      aviso('Médico registrado.', 'ok')
    }

    cerrar()
    cargarMedicos(datos)
  } catch (error) {
    const mensaje = error instanceof ErrorApi ? error.mensaje : 'Error al guardar'
    aviso(mensaje, 'error')
  }
}

async function cambiarEstadoMed(id, nuevoEstado, medico, datos) {
  const btnConfirmar = el('button', { type: 'button', class: 'btn peligro' }, 'Confirmar')
  const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

  const { cerrar } = mostrarModal({
    titulo: nuevoEstado === 'inactivo' ? '¿Desactivar médico?' : '¿Activar médico?',
    contenido: `${medico.nombres} ${medico.apellidos}`,
    botones: [btnConfirmar, btnCancelar]
  })

  btnConfirmar.addEventListener('click', async () => {
    cerrar()
    try {
      await cambiarEstadoMedico(id, { estado: nuevoEstado, confirmar: false })
      aviso(`Estado actualizado a ${nuevoEstado}.`, 'ok')
      cargarMedicos(datos)
    } catch (error) {
      if (error instanceof ErrorApi && error.estado === 409 && error.detalle?.advertencia) {
        const citas = error.detalle.citas || []
        const listaCitas = citas.map(c =>
          `${c.paciente_nombres} ${c.paciente_apellidos} - ${c.fecha} ${c.hora_inicio}`
        ).join('\n')

        const btnConfirmarFinal = el('button', { type: 'button', class: 'btn peligro' }, 'Desactivar de todas formas')
        const btnCancelarFinal = el('button', { type: 'button', class: 'btn secundario' }, 'Cancelar')

        const contenidoAdvertencia = el('div', {}, [
          el('p', {}, 'Este médico tiene citas futuras programadas.'),
          el('div', { style: 'background-color: #f5f5f5; padding: 10px; margin: 15px 0; border-radius: 4px; max-height: 300px; overflow-y: auto;' }, [
            el('strong', {}, 'Citas futuras:'),
            el('pre', { style: 'white-space: pre-wrap; word-wrap: break-word; margin-top: 10px; font-size: 12px;' }, listaCitas),
          ]),
        ])

        const { cerrar: cerrarAdvertencia } = mostrarModal({
          titulo: 'Advertencia',
          contenido: contenidoAdvertencia,
          botones: [btnConfirmarFinal, btnCancelarFinal]
        })

        btnConfirmarFinal.addEventListener('click', async () => {
          cerrarAdvertencia()
          try {
            await cambiarEstadoMedico(id, { estado: nuevoEstado, confirmar: true })
            aviso(`Médico desactivado.`, 'ok')
            cargarMedicos(datos)
          } catch (err) {
            const msg = err instanceof ErrorApi ? err.mensaje : 'Error al desactivar'
            aviso(msg, 'error')
          }
        })

        btnCancelarFinal.addEventListener('click', cerrarAdvertencia)
      } else {
        const mensaje = error instanceof ErrorApi ? error.mensaje : 'Error al cambiar estado'
        aviso(mensaje, 'error')
      }
    }
  })

  btnCancelar.addEventListener('click', cerrar)
}
// ---- CU05: fin ----
