/**
 * CU02 - Gestionar usuarios  (paquete seguridad, Ciclo 1)
 * Actores: Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarUsuarios, crearUsuario, modificarUsuario, cambiarEstadoUsuario } from '../api.js'
import { REQUISITOS_CONTRASENA, validarCrearUsuario, validarModificarUsuario } from '../schemas.js'

export default function CU02GestionarUsuarios(contenedor) {
  PantallaCU02(contenedor)
}

// ---- CU02: inicio ----
function PantallaCU02(contenedor) {
  const datos = { usuarios: [], roles: [], usuarioEditar: null }

  const pantalla = el('div', { class: 'cu02' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Gestionar usuarios'),
      el('div', { class: 'acciones' }, [
        el('button', { class: 'btn', id: 'cu02-nuevo' }, 'Nuevo usuario'),
      ]),
    ]),
    crearBuscador(datos),
    crearTabla(datos),
  ])

  contenedor.append(pantalla)

  document.getElementById('cu02-nuevo').addEventListener('click', () => {
    datos.usuarioEditar = null
    mostrarFormulario(datos)
  })

  cargarUsuarios(datos)
}

function crearBuscador(datos) {
  const filtro = el('input', { type: 'text', id: 'cu02-filtro', placeholder: 'Buscar por usuario o correo...', maxlength: 100 })
  const rol = el('select', { id: 'cu02-rol' }, [
    el('option', { value: '' }, 'Todos los roles'),
  ])
  const estado = el('select', { id: 'cu02-estado' }, [
    el('option', { value: '' }, 'Todos los estados'),
    el('option', { value: 'activo' }, 'Activo'),
    el('option', { value: 'inactivo' }, 'Inactivo'),
    el('option', { value: 'bloqueado' }, 'Bloqueado'),
  ])

  filtro.addEventListener('change', () => cargarUsuarios(datos))
  filtro.addEventListener('input', () => cargarUsuarios(datos))
  rol.addEventListener('change', () => cargarUsuarios(datos))
  estado.addEventListener('change', () => cargarUsuarios(datos))

  return el('div', { class: 'formulario' }, [
    el('div', { class: 'campo' }, [el('label', {}, 'Buscar'), filtro]),
    el('div', { class: 'campo' }, [el('label', {}, 'Rol'), rol]),
    el('div', { class: 'campo' }, [el('label', {}, 'Estado'), estado]),
  ])
}

function crearTabla(datos) {
  return el('div', { class: 'tabla-contenedor' }, [
    el('table', { class: 'tabla', id: 'cu02-tabla' }, [
      el('thead', {}, el('tr', {}, [
        el('th', {}, 'Usuario'),
        el('th', {}, 'Correo'),
        el('th', {}, 'Rol'),
        el('th', {}, 'Estado'),
        el('th', {}, 'Acciones'),
      ])),
      el('tbody', { id: 'cu02-tabla-cuerpo' }),
    ]),
  ])
}

async function cargarUsuarios(datos) {
  const filtro = document.getElementById('cu02-filtro').value.trim() || ''
  const rol = document.getElementById('cu02-rol').value
  const estado = document.getElementById('cu02-estado').value

  try {
    const res = await listarUsuarios(filtro, rol || undefined, estado || undefined)
    datos.usuarios = res.usuarios
    actualizarTabla(datos)
  } catch (e) {
    aviso('error', e instanceof ErrorApi ? e.message : 'No se pudieron cargar los usuarios.')
  }
}

function actualizarTabla(datos) {
  const tbody = document.getElementById('cu02-tabla-cuerpo')
  tbody.innerHTML = ''

  datos.usuarios.forEach((u) => {
    const fila = el('tr', {}, [
      el('td', {}, u.username),
      el('td', {}, u.correo),
      el('td', {}, u.rol),
      el('td', {}, crearBadgeEstado(u.estado)),
      el('td', {}, crearAcciones(u, datos)),
    ])
    tbody.append(fila)
  })

  if (datos.usuarios.length === 0) {
    tbody.append(el('tr', {}, el('td', { colspan: '5' }, 'Sin resultados.')))
  }
}

function crearBadgeEstado(estado) {
  const colores = { activo: '#0d7944', inactivo: '#b5b5b5', bloqueado: '#d42e3c' }
  return el('span', { style: `background:${colores[estado]}; color:#fff; padding:2px 6px; border-radius:3px; font-size:12px` }, estado)
}

function crearAcciones(u, datos) {
  const botones = el('div', { style: 'display:flex; gap:4px; flex-wrap:wrap' })

  const editar = el('button', { class: 'btn', style: 'padding:4px 8px; font-size:12px' }, 'Editar')
  editar.addEventListener('click', () => {
    datos.usuarioEditar = u
    mostrarFormulario(datos)
  })
  botones.append(editar)

  if (u.puede_activar) {
    const activar = el('button', { class: 'btn', style: 'padding:4px 8px; font-size:12px' }, 'Activar')
    activar.addEventListener('click', () => cambiarEstadoConConfirmacion(u.id_usuario, 'activo', datos))
    botones.append(activar)
  }

  if (u.puede_desactivar) {
    const desactivar = el('button', { class: 'btn peligro', style: 'padding:4px 8px; font-size:12px' }, 'Desactivar')
    desactivar.addEventListener('click', () => cambiarEstadoConConfirmacion(u.id_usuario, 'inactivo', datos))
    botones.append(desactivar)
  }

  if (u.puede_desbloquear) {
    const desbloquear = el('button', { class: 'btn', style: 'padding:4px 8px; font-size:12px' }, 'Desbloquear')
    desbloquear.addEventListener('click', () => cambiarEstadoConConfirmacion(u.id_usuario, 'activo', datos))
    botones.append(desbloquear)
  }

  return botones
}

async function cambiarEstadoConConfirmacion(idUsuario, estado, datos) {
  const mensajes = { activo: 'activar', inactivo: 'desactivar', bloqueado: 'bloquear' }
  if (!confirm(`¿Desea ${mensajes[estado]} este usuario?`)) return

  try {
    await cambiarEstadoUsuario(idUsuario, estado)
    aviso('éxito', 'Estado actualizado correctamente.')
    cargarUsuarios(datos)
  } catch (e) {
    aviso('error', e instanceof ErrorApi ? e.message : 'No se pudo cambiar el estado.')
  }
}

function mostrarFormulario(datos) {
  const modal = document.querySelector('.cu02-modal')
  if (modal) modal.remove()

  const usuario = datos.usuarioEditar
  const esEdicion = usuario !== null
  const titulo = esEdicion ? `Editar usuario: ${usuario.username}` : 'Nuevo usuario'

  const form = el('form', {}, [
    el('div', { class: 'campo completo' }, [
      el('label', {}, 'Nombre de usuario'),
      el('input', { type: 'text', name: 'username', maxlength: 40, value: usuario?.username || '' }),
    ]),
    el('div', { class: 'campo completo' }, [
      el('label', {}, 'Correo'),
      el('input', { type: 'email', name: 'correo', maxlength: 100, value: usuario?.correo || '' }),
    ]),
  ])

  if (!esEdicion) {
    const seccionContrasena = el('div', { id: 'cu02-seccion-contrasena' })
    const checkGenerar = el('input', { type: 'checkbox', id: 'cu02-generar-contrasena', checked: true })
    const labelGenerar = el('label', { for: 'cu02-generar-contrasena', style: 'display:inline; margin-left:8px' }, 'Generar contraseña aleatoria')

    const campoContrasena = el('div', { class: 'campo completo', id: 'cu02-campo-contrasena', style: 'display:none' }, [
      el('label', {}, 'Contraseña'),
      el('input', { type: 'password', name: 'contrasena', maxlength: 72 }),
    ])

    const requisitos = el('div', { id: 'cu02-requisitos', style: 'display:none; margin-top:8px' })
    const inputContrasena = campoContrasena.querySelector('input')

    checkGenerar.addEventListener('change', () => {
      if (checkGenerar.checked) {
        campoContrasena.style.display = 'none'
        requisitos.style.display = 'none'
        inputContrasena.value = ''
      } else {
        campoContrasena.style.display = 'block'
        requisitos.style.display = 'block'
        inputContrasena.focus()
      }
    })

    inputContrasena.addEventListener('input', () => {
      const c = inputContrasena.value
      requisitos.innerHTML = ''
      REQUISITOS_CONTRASENA.forEach((r) => {
        const cumple = r.cumple(c)
        const color = cumple ? '#0d7944' : '#999'
        requisitos.append(
          el('div', { style: `color:${color}; font-size:12px` }, (cumple ? '✓ ' : '○ ') + r.texto)
        )
      })
    })

    seccionContrasena.append(checkGenerar, labelGenerar, campoContrasena, requisitos)
    form.append(seccionContrasena)
  }

  const selectRol = el('select', { name: 'rol' }, [
    el('option', { value: '' }, 'Seleccione un rol'),
    el('option', { value: 'Administrador' }, 'Administrador'),
    el('option', { value: 'Recepcionista' }, 'Recepcionista'),
    el('option', { value: 'Médico' }, 'Médico'),
    el('option', { value: 'Paciente' }, 'Paciente'),
  ])
  if (usuario) selectRol.value = usuario.rol

  form.append(el('div', { class: 'campo completo' }, [
    el('label', {}, 'Rol'),
    selectRol,
  ]))

  const overlayModal = el('div', { class: 'cu02-modal', style: 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000' }, [
    el('div', { style: 'background:#fff; border-radius:8px; padding:20px; max-width:500px; width:90%; max-height:90vh; overflow-y:auto' }, [
      el('h2', {}, titulo),
      form,
      el('div', { style: 'display:flex; gap:8px; margin-top:20px' }, [
        el('button', { type: 'submit', class: 'btn', form: 'cu02-form-usuario' }, esEdicion ? 'Guardar cambios' : 'Crear usuario'),
        el('button', { type: 'button', class: 'btn secundario', id: 'cu02-cancelar' }, 'Cancelar'),
      ]),
    ]),
  ])
  form.id = 'cu02-form-usuario'
  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    await guardarUsuario(esEdicion ? usuario.id_usuario : null, form, datos, overlayModal)
  })

  document.getElementById('cu02-cancelar')?.remove?.()
  const botonesCancelar = overlayModal.querySelectorAll('#cu02-cancelar')
  if (botonesCancelar.length > 0) {
    botonesCancelar[0].addEventListener('click', () => overlayModal.remove())
  } else {
    const btn = overlayModal.querySelector('#cu02-cancelar') || overlayModal.querySelector('button:last-child')
    if (btn && btn.textContent === 'Cancelar') {
      btn.addEventListener('click', () => overlayModal.remove())
    }
  }

  const cancelarBtn = overlayModal.querySelector('#cu02-cancelar')
  if (cancelarBtn) {
    cancelarBtn.addEventListener('click', () => overlayModal.remove())
  }

  document.body.append(overlayModal)
  form.querySelector('input').focus()
}

async function guardarUsuario(idUsuario, form, datos, modal) {
  const formData = new FormData(form)
  const datosForm = Object.fromEntries(formData)

  const validar = idUsuario ? validarModificarUsuario : validarCrearUsuario
  const errores = validar(datosForm)

  if (Object.keys(errores).length > 0) {
    const msgs = Object.values(errores).join('\n')
    aviso('error', msgs)
    return
  }

  try {
    const datos_envio = {
      username: datosForm.username.trim(),
      correo: datosForm.correo.trim(),
      rol: datosForm.rol,
    }

    if (!idUsuario && datosForm.contrasena) {
      datos_envio.contrasena = datosForm.contrasena
    }

    if (idUsuario) {
      await modificarUsuario(idUsuario, datos_envio)
    } else {
      await crearUsuario(datos_envio)
    }

    aviso('éxito', idUsuario ? 'Usuario modificado correctamente.' : 'Usuario creado correctamente.')
    modal.remove()
    cargarUsuarios(datos)
  } catch (e) {
    const msg = e instanceof ErrorApi ? e.message : 'No se pudo guardar el usuario.'
    aviso('error', msg)
  }
}
// ---- CU02: fin ----
