import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { NOMBRE_SISTEMA } from '../../../nucleo/config.js'
import { registrarCuenta } from '../api.js'

export default function CU16RegistroPaciente(contenedor) {
  const panel = el('div', { class: 'acceso-tarjeta' })
  contenedor.replaceChildren(el('div', { class: 'acceso' }, [
    el('aside', { class: 'acceso-marca' }, [
      el('p', { class: 'acceso-logo' }, [el('span', { class: 'cruz', 'aria-hidden': 'true' }, '✚'), NOMBRE_SISTEMA]),
      el('h2', {}, 'Gestión de citas médicas'),
      el('p', {}, 'Programación de citas, agenda de los médicos e historial de los pacientes en un solo lugar.'),
    ]),
    el('section', { class: 'acceso-panel' }, [panel]),
  ]))

  const mensajeError = el('div', { id: 'cu16-error', class: 'mensaje-error', style: 'display: none; margin-bottom: 16px; padding: 12px; background: #fee; border: 1px solid #f99; color: #c33; border-radius: 4px;' })

  const form = el('form', { class: 'formulario', novalidate: true }, [
    el('h1', {}, 'Crear tu cuenta'),
    el('p', { class: 'suave' }, 'Regístrate como paciente'),
    mensajeError,

    el('div', { class: 'campo' }, [
      el('label', {}, 'Cédula de identidad'),
      el('input', { type: 'text', id: 'cu16-ci', placeholder: 'Número de CI', minlength: '5', maxlength: '10' }),
    ]),

    el('div', { class: 'campo' }, [
      el('label', {}, 'Fecha de nacimiento'),
      el('input', { type: 'date', id: 'cu16-fecha_nacimiento' }),
    ]),

    el('div', { class: 'campo' }, [
      el('label', {}, 'Usuario (login)'),
      el('input', { type: 'text', id: 'cu16-login', placeholder: 'Tu nombre de usuario' }),
    ]),

    el('div', { class: 'campo' }, [
      el('label', {}, 'Contraseña'),
      el('input', { type: 'password', id: 'cu16-contrasena', placeholder: 'Mínimo 8 caracteres' }),
    ]),

    el('div', { class: 'campo' }, [
      el('label', {}, 'Confirmar contraseña'),
      el('input', { type: 'password', id: 'cu16-contrasena2', placeholder: 'Repite tu contraseña' }),
    ]),

    el('button', { type: 'submit', class: 'btn btn-bloque' }, 'Crear cuenta'),

    el('p', { class: 'suave', style: 'text-align: center; margin-top: 16px' }, [
      '¿Ya tienes cuenta? ',
      el('a', { class: 'enlace', href: '#/login' }, 'Inicia sesión'),
    ]),
  ])

  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    const ci = document.getElementById('cu16-ci').value.trim()
    const fechaNacimiento = document.getElementById('cu16-fecha_nacimiento').value.trim()
    const login = document.getElementById('cu16-login').value.trim()
    const contrasena = document.getElementById('cu16-contrasena').value
    const contrasena2 = document.getElementById('cu16-contrasena2').value

    if (!ci || !fechaNacimiento || !login || !contrasena || !contrasena2) {
      aviso('Todos los campos son requeridos.', 'error')
      return
    }

    if (!/^\d+$/.test(ci)) {
      aviso('El CI debe contener solo dígitos.', 'error')
      return
    }

    if (ci.length < 5 || ci.length > 10) {
      aviso('El CI debe tener entre 5 y 10 dígitos.', 'error')
      return
    }

    if (contrasena !== contrasena2) {
      aviso('Las contraseñas no coinciden.', 'error')
      return
    }

    if (contrasena.length < 8) {
      aviso('La contraseña debe tener al menos 8 caracteres.', 'error')
      return
    }

    try {
      mensajeError.style.display = 'none'
      const res = await registrarCuenta(ci, fechaNacimiento, login, contrasena)
      aviso(res.mensaje, 'ok')
      setTimeout(() => { window.location.hash = '#/login' }, 1500)
    } catch (e) {
      const msg = e instanceof ErrorApi ? e.message : (e.message || 'Error al crear la cuenta.')
      mensajeError.textContent = msg
      mensajeError.style.display = 'block'
    }
  })

  panel.replaceChildren(form)
}
