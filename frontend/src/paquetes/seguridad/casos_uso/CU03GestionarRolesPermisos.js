/**
 * CU03 - Gestionar roles y permisos  (paquete seguridad, Ciclo 1)
 * Actores: Administrador
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: IMPLEMENTADO.
 */
import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarRoles, listarPermisos, obtenerPermisosRol, asignarPermisos } from '../api.js'

export default function CU03GestionarRolesPermisos(contenedor) {
  PantallaCU03(contenedor)
}

// ---- CU03: inicio ----
function PantallaCU03(contenedor) {
  const datos = { roles: [], permisos: [], rolActual: null, permisosActuales: [], permisosSeleccionados: new Set() }

  const pantalla = el('div', { class: 'cu03' }, [
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, 'Gestionar roles y permisos'),
    ]),
    crearContenedorDual(datos),
  ])

  contenedor.append(pantalla)
  cargarDatos(datos)
}

function crearContenedorDual(datos) {
  return el('div', { style: 'display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 20px; min-height: 600px' }, [
    crearPanelRoles(datos),
    crearPanelPermisos(datos),
  ])
}

function crearPanelRoles(datos) {
  return el('div', { style: 'background: #f8f9fa; border-radius: 8px; padding: 16px; border: 1px solid #e0e0e0' }, [
    el('h3', { style: 'margin: 0 0 16px; color: #333' }, 'Roles'),
    el('div', { id: 'cu03-lista-roles', style: 'overflow-y: auto; max-height: 500px' }),
  ])
}

function crearPanelPermisos(datos) {
  return el('div', { style: 'background: #fff; border-radius: 8px; padding: 16px; border: 1px solid #e0e0e0' }, [
    el('h3', { id: 'cu03-titulo-rol', style: 'margin: 0 0 16px; color: #333' }, 'Selecciona un rol'),
    el('div', { id: 'cu03-estado-rol', style: 'font-size: 12px; color: #666; margin-bottom: 12px' }),
    el('div', { id: 'cu03-permisos-grupo' }),
    el('div', { id: 'cu03-botones', class: 'acciones', style: 'margin-top: 20px; display: none' }, [
      el('button', { class: 'btn', id: 'cu03-guardar' }, 'Guardar cambios'),
      el('button', { class: 'btn secundario', id: 'cu03-cancelar' }, 'Cancelar'),
    ]),
  ])
}

async function cargarDatos(datos) {
  try {
    const resRoles = await listarRoles()
    const resPermisos = await listarPermisos()
    datos.roles = resRoles.roles
    datos.permisos = resPermisos.permisos
    actualizarListaRoles(datos)
  } catch (e) {
    aviso('error', e instanceof ErrorApi ? e.message : 'No se pudieron cargar los datos.')
  }
}

function actualizarListaRoles(datos) {
  const lista = document.getElementById('cu03-lista-roles')
  lista.innerHTML = ''

  datos.roles.forEach((rol) => {
    const elemento = el('div', {
      class: 'cu03-rol-item',
      style: `padding: 12px; margin-bottom: 8px; border-radius: 4px; cursor: pointer;
              background: ${datos.rolActual?.id_rol === rol.id_rol ? '#134E4A' : '#e8f5f4'};
              color: ${datos.rolActual?.id_rol === rol.id_rol ? '#fff' : '#333'};
              border: 1px solid ${datos.rolActual?.id_rol === rol.id_rol ? '#0d3a37' : '#d0e8e6'};`,
    }, [
      el('div', { style: 'font-weight: 600' }, rol.nombre),
      el('div', { style: 'font-size: 12px; opacity: 0.8; margin-top: 4px' }, `Estado: ${rol.estado}`),
    ])

    elemento.addEventListener('click', () => {
      seleccionarRol(datos, rol)
    })

    lista.append(elemento)
  })
}

async function seleccionarRol(datos, rol) {
  try {
    const res = await obtenerPermisosRol(rol.id_rol)
    datos.rolActual = rol
    datos.permisosActuales = res.permisos.map((p) => p.id_permiso)
    datos.permisosSeleccionados = new Set(datos.permisosActuales)

    document.getElementById('cu03-titulo-rol').textContent = rol.nombre
    document.getElementById('cu03-estado-rol').textContent = `Estado: ${rol.estado}`

    actualizarListaRoles(datos)
    renderizarPermisos(datos)
    mostrarBotones(datos, rol.estado === 'activo')
  } catch (e) {
    aviso('error', e instanceof ErrorApi ? e.message : 'No se pudieron cargar los permisos.')
  }
}

function renderizarPermisos(datos) {
  const contenedor = document.getElementById('cu03-permisos-grupo')
  contenedor.innerHTML = ''

  const grupos = {}
  datos.permisos.forEach((p) => {
    const prefix = p.codigo.split('.')[0]
    if (!grupos[prefix]) {
      grupos[prefix] = []
    }
    grupos[prefix].push(p)
  })

  Object.keys(grupos)
    .sort()
    .forEach((prefix) => {
      const grupo = el('div', { style: 'margin-bottom: 24px' }, [
        el('h4', { style: 'margin: 0 0 12px; font-size: 14px; color: #134E4A; text-transform: uppercase; letter-spacing: 0.5px' }, prefix),
      ])

      grupos[prefix].forEach((permiso) => {
        const isChecked = datos.permisosSeleccionados.has(permiso.id_permiso)
        const checkbox = el('input', {
          type: 'checkbox',
          id: `cu03-perm-${permiso.id_permiso}`,
          checked: isChecked,
        })

        checkbox.addEventListener('change', () => {
          if (checkbox.checked) {
            datos.permisosSeleccionados.add(permiso.id_permiso)
          } else {
            datos.permisosSeleccionados.delete(permiso.id_permiso)
          }
        })

        const label = el('label', { style: 'display: flex; align-items: center; margin-bottom: 8px; cursor: pointer; font-size: 14px' }, [
          checkbox,
          el('span', { style: 'margin-left: 8px' }, `${permiso.codigo} — ${permiso.descripcion}`),
        ])

        grupo.append(label)
      })

      contenedor.append(grupo)
    })
}

function mostrarBotones(datos, mostrar) {
  const botones = document.getElementById('cu03-botones')
  botones.style.display = mostrar ? 'flex' : 'none'

  if (mostrar) {
    document.getElementById('cu03-guardar').onclick = () => guardarCambios(datos)
    document.getElementById('cu03-cancelar').onclick = () => cancelarCambios(datos)
  }
}

async function guardarCambios(datos) {
  if (!datos.rolActual) return

  const permisosNuevos = Array.from(datos.permisosSeleccionados)

  if (JSON.stringify(permisosNuevos.sort()) === JSON.stringify(datos.permisosActuales.sort())) {
    aviso('info', 'No hay cambios para guardar.')
    return
  }

  const confirmar = confirm(
    `¿Confirma que desea cambiar los permisos del rol "${datos.rolActual.nombre}"?\n\nLos cambios se aplicarán en el siguiente inicio de sesión de los usuarios con este rol.`
  )
  if (!confirmar) return

  try {
    const res = await asignarPermisos(datos.rolActual.id_rol, permisosNuevos)
    datos.permisosActuales = permisosNuevos
    aviso('exito', res.mensaje)
    seleccionarRol(datos, datos.rolActual)
  } catch (e) {
    aviso('error', e instanceof ErrorApi ? e.message : 'Error al guardar los cambios.')
  }
}

function cancelarCambios(datos) {
  datos.permisosSeleccionados = new Set(datos.permisosActuales)
  renderizarPermisos(datos)
}
// ---- CU03: fin ----
