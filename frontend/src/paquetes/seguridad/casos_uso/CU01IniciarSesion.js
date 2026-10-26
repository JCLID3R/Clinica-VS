/**
 * CU01 - Iniciar y cerrar sesión  (paquete seguridad, Ciclo 1)
 * Actores: Administrador, Recepcionista, Médico, Paciente
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import CasoUsoPendiente from '../../../componentes/CasoUsoPendiente.js'

export default function CU01IniciarSesion(contenedor) {
  PantallaCU01(contenedor)
}

// ---- CU01: inicio ----
import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { NOMBRE_SISTEMA } from '../../../nucleo/config.js'
import { obtenerUsuario, establecerUsuario, irA } from '../../../nucleo/auth/sesion.js'
import { iniciarSesion, recuperarContrasena, cambiarContrasena } from '../api.js'
import { REQUISITOS_CONTRASENA, validarLogin, validarRecuperar, validarCambioContrasena } from '../schemas.js'

/**
 * Ruta #/login:
 *   - sin sesión: inicio de sesión y recuperación de contraseña;
 *   - con contraseña temporal: cambio obligatorio (no deja usar el resto del sistema);
 *   - con sesión: «Mi cuenta» para cambiar la contraseña.
 */
function PantallaCU01(contenedor) {
  const usuario = obtenerUsuario()
  if (!usuario) pantallaAcceso(contenedor)
  else pantallaCuenta(contenedor, usuario)
}

// ---- Piezas del formulario ----

/** Campo con etiqueta, mensaje de error y, si es contraseña, botón Mostrar/Ocultar. */
function crearCampo({ nombre, etiqueta, tipo = 'text', autocompletar, contrasena = false, completo = true }) {
  const id = `cu01-${nombre}`
  const input = el('input', {
    id, name: nombre, type: contrasena ? 'password' : tipo, autocomplete: autocompletar,
    'aria-describedby': `${id}-error`, maxlength: contrasena ? 72 : 100,
  })
  const error = el('span', { class: 'error-campo', id: `${id}-error`, 'aria-live': 'polite' })
  let control = input
  if (contrasena) {
    const ver = el('button', { class: 'btn-ver-clave', type: 'button', 'aria-pressed': 'false', 'aria-controls': id }, 'Mostrar')
    ver.addEventListener('click', () => {
      const mostrar = input.type === 'password'
      input.type = mostrar ? 'text' : 'password'
      ver.textContent = mostrar ? 'Ocultar' : 'Mostrar'
      ver.setAttribute('aria-pressed', String(mostrar))
      input.focus()
    })
    control = el('div', { class: 'campo-clave' }, [input, ver])
  }
  const nodo = el('div', { class: completo ? 'campo completo' : 'campo' }, [el('label', { for: id }, etiqueta), control, error])
  return { nodo, input, error }
}

/** Muestra los errores por campo ({ campo: mensaje }) y enfoca el primero. Devuelve true si hay errores. */
function mostrarErrores(campos, errores) {
  let primero = null
  for (const [nombre, c] of Object.entries(campos)) {
    const mensaje = errores[nombre] || ''
    c.error.textContent = mensaje
    if (mensaje) c.input.setAttribute('aria-invalid', 'true')
    else c.input.removeAttribute('aria-invalid')
    if (mensaje && !primero) primero = c.input
  }
  if (primero) primero.focus()
  return primero !== null
}

function crearAlerta() {
  const nodo = el('p', { class: 'alerta', role: 'alert' })
  nodo.hidden = true
  return {
    nodo,
    mostrar(texto, tipo = 'error') { nodo.textContent = texto; nodo.className = `alerta ${tipo}`; nodo.hidden = false },
    ocultar() { nodo.hidden = true },
  }
}

function mensajeDeError(e) {
  return e instanceof ErrorApi ? e.message : 'No se pudo conectar con el servidor. Intente nuevamente.'
}

/** Ejecuta el envío deshabilitando el botón mientras espera la respuesta. */
async function enviando(boton, textoEspera, accion) {
  const texto = boton.textContent
  boton.disabled = true
  boton.textContent = textoEspera
  try { await accion() } finally { boton.disabled = false; boton.textContent = texto }
}

// ---- Inicio de sesión y recuperación (sin sesión) ----

function pantallaAcceso(contenedor) {
  const panel = el('div', { class: 'acceso-tarjeta' })
  contenedor.append(el('div', { class: 'acceso' }, [
    el('aside', { class: 'acceso-marca' }, [
      el('p', { class: 'acceso-logo' }, [el('span', { class: 'cruz', 'aria-hidden': 'true' }, '✚'), NOMBRE_SISTEMA]),
      el('h2', {}, 'Gestión de citas médicas'),
      el('p', {}, 'Programación de citas, agenda de los médicos e historial de los pacientes en un solo lugar.'),
    ]),
    el('section', { class: 'acceso-panel' }, [panel]),
  ]))
  formularioLogin(panel)
}

function formularioLogin(panel) {
  const alerta = crearAlerta()
  const campos = {
    login: crearCampo({ nombre: 'login', etiqueta: 'Usuario o correo electrónico', autocompletar: 'username' }),
    contrasena: crearCampo({ nombre: 'contrasena', etiqueta: 'Contraseña', autocompletar: 'current-password', contrasena: true }),
  }
  const boton = el('button', { class: 'btn btn-bloque', type: 'submit' }, 'Iniciar sesión')
  const olvido = el('a', { class: 'enlace', href: '#/login' }, '¿Olvidaste tu contraseña?')
  olvido.addEventListener('click', (e) => { e.preventDefault(); formularioRecuperar(panel) })

  const form = el('form', { class: 'formulario', novalidate: true }, [
    campos.login.nodo, campos.contrasena.nodo, el('div', { class: 'completo' }, [boton]),
  ])
  form.addEventListener('submit', (e) => {
    e.preventDefault()
    alerta.ocultar()
    const datos = { login: campos.login.input.value, contrasena: campos.contrasena.input.value }
    if (mostrarErrores(campos, validarLogin(datos))) return
    enviando(boton, 'Ingresando…', async () => {
      try {
        const usuario = await iniciarSesion(datos.login.trim(), datos.contrasena)
        establecerUsuario(usuario)
        if (usuario.debe_cambiar_contrasena) {
          irA('/login')
        } else {
          aviso(`Bienvenido, ${usuario.username}.`)
          irA('/')
        }
      } catch (err) {
        if (err instanceof ErrorApi && err.estado === 400) mostrarErrores(campos, err.detalle)
        alerta.mostrar(mensajeDeError(err), err.estado === 423 ? 'advertencia' : 'error')
        campos.contrasena.input.value = ''
        campos.contrasena.input.focus()
      }
    })
  })

  panel.replaceChildren(
    el('h1', {}, 'Iniciar sesión'),
    el('p', { class: 'acceso-ayuda' }, 'Ingrese con el usuario o correo que le asignó la clínica.'),
    alerta.nodo, form,
    el('p', { class: 'acceso-pie' }, [olvido]),
  )
  campos.login.input.focus()
}

function formularioRecuperar(panel) {
  const alerta = crearAlerta()
  const campos = { correo: crearCampo({ nombre: 'correo', etiqueta: 'Correo electrónico', tipo: 'email', autocompletar: 'email' }) }
  const boton = el('button', { class: 'btn btn-bloque', type: 'submit' }, 'Enviar contraseña temporal')
  const volver = el('a', { class: 'enlace', href: '#/login' }, 'Volver al inicio de sesión')
  volver.addEventListener('click', (e) => { e.preventDefault(); formularioLogin(panel) })

  const form = el('form', { class: 'formulario', novalidate: true }, [campos.correo.nodo, el('div', { class: 'completo' }, [boton])])
  form.addEventListener('submit', (e) => {
    e.preventDefault()
    alerta.ocultar()
    const datos = { correo: campos.correo.input.value }
    if (mostrarErrores(campos, validarRecuperar(datos))) return
    enviando(boton, 'Enviando…', async () => {
      try {
        const r = await recuperarContrasena(datos.correo.trim())
        alerta.mostrar(`${r.mensaje} Revise también la carpeta de spam.`, 'ok')
        form.reset()
      } catch (err) {
        if (err instanceof ErrorApi && err.estado === 400) mostrarErrores(campos, err.detalle)
        alerta.mostrar(mensajeDeError(err))
      }
    })
  })

  panel.replaceChildren(
    el('h1', {}, 'Recuperar contraseña'),
    el('p', { class: 'acceso-ayuda' }, 'Escriba el correo de su cuenta. Le enviaremos una contraseña temporal que deberá cambiar al ingresar.'),
    alerta.nodo, form,
    el('p', { class: 'acceso-pie' }, [volver]),
  )
  campos.correo.input.focus()
}

// ---- Mi cuenta y cambio de contraseña (con sesión) ----

/** Lista de requisitos que se marcan en verde a medida que se cumplen. */
function listaRequisitos(nueva, confirmacion) {
  const items = [
    ...REQUISITOS_CONTRASENA.map((r) => ({ texto: r.texto, cumple: () => r.cumple(nueva.value) })),
    { texto: 'La confirmación coincide', cumple: () => confirmacion.value !== '' && confirmacion.value === nueva.value },
  ].map((r) => {
    const estado = el('span', { class: 'sr-solo' })
    const li = el('li', {}, [el('span', { class: 'marca-req', 'aria-hidden': 'true' }), r.texto, estado])
    return { li, estado, cumple: r.cumple }
  })
  const actualizar = () => items.forEach((i) => {
    const ok = i.cumple()
    i.li.classList.toggle('cumple', ok)
    i.estado.textContent = ok ? ' (cumple)' : ' (falta)'
  })
  nueva.addEventListener('input', actualizar)
  confirmacion.addEventListener('input', actualizar)
  actualizar()
  return el('ul', { class: 'requisitos completo', 'aria-label': 'Requisitos de la contraseña' }, items.map((i) => i.li))
}

function pantallaCuenta(contenedor, usuario) {
  const obligatorio = usuario.debe_cambiar_contrasena
  const alerta = crearAlerta()
  const campos = {
    actual: crearCampo({
      nombre: 'actual', etiqueta: obligatorio ? 'Contraseña temporal (la que recibió por correo)' : 'Contraseña actual',
      autocompletar: 'current-password', contrasena: true,
    }),
    nueva: crearCampo({ nombre: 'nueva', etiqueta: 'Nueva contraseña', autocompletar: 'new-password', contrasena: true, completo: false }),
    confirmacion: crearCampo({ nombre: 'confirmacion', etiqueta: 'Confirmar nueva contraseña', autocompletar: 'new-password', contrasena: true, completo: false }),
  }
  const boton = el('button', { class: 'btn', type: 'submit' }, 'Cambiar contraseña')
  const form = el('form', { class: 'formulario', novalidate: true }, [
    campos.actual.nodo, campos.nueva.nodo, campos.confirmacion.nodo,
    listaRequisitos(campos.nueva.input, campos.confirmacion.input),
    el('div', { class: 'acciones completo' }, [boton]),
  ])
  form.addEventListener('submit', (e) => {
    e.preventDefault()
    alerta.ocultar()
    const datos = { actual: campos.actual.input.value, nueva: campos.nueva.input.value, confirmacion: campos.confirmacion.input.value }
    if (mostrarErrores(campos, validarCambioContrasena(datos))) return
    enviando(boton, 'Guardando…', async () => {
      try {
        const r = await cambiarContrasena(datos)
        establecerUsuario(r.usuario)
        aviso('Contraseña cambiada. Le enviamos un aviso a su correo.')
        if (obligatorio) {
          irA('/')
        } else {
          form.reset()
          form.querySelectorAll('input').forEach((i) => i.dispatchEvent(new Event('input')))
          alerta.mostrar('La contraseña se cambió correctamente. Le enviamos un aviso a su correo.', 'ok')
        }
      } catch (err) {
        if (err instanceof ErrorApi && err.estado === 400) mostrarErrores(campos, err.detalle)
        alerta.mostrar(mensajeDeError(err))
      }
    })
  })

  const tarjetaCambio = el('section', { class: 'tarjeta tarjeta-cu01' }, [el('h3', {}, 'Cambiar contraseña'), alerta.nodo, form])
  if (obligatorio) {
    contenedor.append(
      el('div', { class: 'encabezado-pagina' }, [el('h1', {}, 'Cambiar contraseña')]),
      el('p', { class: 'alerta advertencia' }, 'Ingresó con una contraseña temporal. Para continuar, cree una contraseña nueva.'),
      tarjetaCambio,
    )
  } else {
    contenedor.append(
      el('div', { class: 'encabezado-pagina' }, [el('h1', {}, 'Mi cuenta')]),
      el('div', { class: 'cu01-cuenta' }, [
        el('section', { class: 'tarjeta' }, [
          el('h3', {}, 'Datos de acceso'),
          el('dl', { class: 'datos-cuenta' }, [
            el('dt', {}, 'Usuario'), el('dd', {}, usuario.username),
            el('dt', {}, 'Correo'), el('dd', {}, usuario.correo || '—'),
            el('dt', {}, 'Rol'), el('dd', {}, usuario.rol),
          ]),
          el('p', { class: 'acceso-ayuda' }, 'Para salir del sistema use «Cerrar sesión» en la parte superior.'),
        ]),
        tarjetaCambio,
      ]),
    )
  }
  campos.actual.input.focus()
}
// ---- CU01: fin ----
