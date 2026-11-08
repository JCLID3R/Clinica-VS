import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { obtenerPerfil, actualizarPerfil } from '../api.js'
import { cambiarContrasena } from '../../seguridad/api.js'

export default function CU16GestionarPerfilPaciente(contenedor) {
  let perfil = null

  function formatearFecha(fecha) {
    if (!fecha) return ''
    const [y, m, d] = fecha.split('-')
    return `${d}/${m}/${y}`
  }

  async function cargarPerfil() {
    try {
      const res = await obtenerPerfil()
      perfil = res.paciente
      mostrarPerfil()
    } catch (e) {
      aviso(contenedor, e instanceof ErrorApi ? e.message : 'Error al cargar el perfil.', 'error')
    }
  }

  function mostrarPerfil() {
    const tarjetaDatos = el('div', { class: 'tarjeta' }, [
      el('h3', {}, 'Datos personales'),
      el('p', {}, [el('strong', {}, 'Cédula: '), el('span', {}, perfil.ci)]),
      el('p', {}, [el('strong', {}, 'Nombres: '), el('span', {}, perfil.nombres)]),
      el('p', {}, [el('strong', {}, 'Apellidos: '), el('span', {}, perfil.apellidos)]),
      el('p', {}, [el('strong', {}, 'Fecha de nacimiento: '), el('span', {}, formatearFecha(perfil.fecha_nacimiento))]),
    ])

    const inputTelefono = el('input', { type: 'tel', id: 'telefono', value: perfil.telefono || '' })
    const inputCorreo = el('input', { type: 'email', id: 'correo', value: perfil.correo || '' })
    const inputDireccion = el('input', { type: 'text', id: 'direccion', value: perfil.direccion || '' })

    const formContacto = el('form', { class: 'formulario' }, [
      el('div', { class: 'campo' }, [
        el('label', {}, 'Teléfono'),
        inputTelefono,
      ]),
      el('div', { class: 'campo' }, [
        el('label', {}, 'Correo'),
        inputCorreo,
      ]),
      el('div', { class: 'campo' }, [
        el('label', {}, 'Dirección'),
        inputDireccion,
      ]),
      el('button', { type: 'submit', class: 'btn' }, 'Guardar cambios'),
    ])

    formContacto.addEventListener('submit', async (e) => {
      e.preventDefault()
      const datos = {
        telefono: inputTelefono.value.trim(),
        correo: inputCorreo.value.trim(),
        direccion: inputDireccion.value.trim(),
      }
      try {
        await actualizarPerfil(datos)
        aviso('Datos actualizados correctamente.', 'ok')
      } catch (e) {
        aviso(e instanceof ErrorApi ? e.message : 'Error al actualizar.', 'error')
      }
    })

    const tarjetaContacto = el('div', { class: 'tarjeta' }, [
      el('h3', {}, 'Datos de contacto'),
      formContacto,
    ])

    const contenedorTarjetas = el('div', { class: 'grid-tarjetas', style: 'display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0' }, [
      tarjetaDatos,
      tarjetaContacto,
    ])

    const btnCambiarContrasena = el('button', { type: 'button', class: 'btn' }, 'Cambiar contraseña')
    btnCambiarContrasena.addEventListener('click', () => {
      window.location.hash = '#/seguridad/mi-cuenta'
    })

    contenedor.replaceChildren(
      el('div', { class: 'encabezado-pagina' }, el('h1', {}, 'Mi perfil')),
      contenedorTarjetas,
      el('div', { class: 'acciones', style: 'margin-top: 20px' }, btnCambiarContrasena)
    )
  }

  cargarPerfil()
}
