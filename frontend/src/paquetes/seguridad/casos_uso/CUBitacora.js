/**
 * CUBitacora.js
 * Pantalla de bitácora del sistema con filtros, paginación y exportación CSV.
 */
import { el } from '../../../componentes/comunes.js'
import { listarBitacora, exportarBitacoraCsv } from '../api.js'
import { icono } from '../../../componentes/iconos.js'

// ---- BITACORA: inicio ----
export default async function CUBitacora(contenedor) {
  const btnExportar = el('button', { class: 'btn', id: 'btn-exportar' })
  btnExportar.append(icono.bitacora(18), 'Exportar CSV')

  contenedor.append(
    el('div', { class: 'encabezado-pagina' }, [
      el('h1', {}, [icono.bitacora(24), 'Bitácora']),
      btnExportar,
    ]),
    el('p', {}, 'Registro de las acciones de los usuarios: inicios y cierres de sesión, intentos fallidos y cada operación realizada. No se puede modificar ni borrar.'),
  )

  // Filtros
  const filtroUsuario = el('input', { type: 'text', placeholder: 'Ej: admin', id: 'filtro-usuario' })
  const filtroTabla = el('select', { id: 'filtro-tabla' }, [
    el('option', { value: '' }, 'Todos'),
    el('option', { value: 'usuario' }, 'Usuario'),
    el('option', { value: 'rol' }, 'Rol'),
    el('option', { value: 'paciente' }, 'Paciente'),
    el('option', { value: 'medico' }, 'Médico'),
    el('option', { value: 'especialidad' }, 'Especialidad'),
    el('option', { value: 'horario_atencion' }, 'Horario'),
    el('option', { value: 'cita' }, 'Cita'),
  ])
  const filtroAccion = el('select', { id: 'filtro-accion' }, [
    el('option', { value: '' }, 'Todas'),
    el('option', { value: 'USUARIO_CREAR' }, 'Usuario creado'),
    el('option', { value: 'USUARIO_MODIFICAR' }, 'Usuario modificado'),
    el('option', { value: 'USUARIO_ESTADO' }, 'Usuario estado'),
    el('option', { value: 'INICIO_SESION' }, 'Inicio de sesión'),
    el('option', { value: 'CIERRE_SESION' }, 'Cierre de sesión'),
    el('option', { value: 'INICIO_FALLIDO' }, 'Inicio fallido'),
    el('option', { value: 'BLOQUEO_INTENTOS' }, 'Bloqueo'),
  ])
  const filtroDesde = el('input', { type: 'date', id: 'filtro-desde' })
  const filtroHasta = el('input', { type: 'date', id: 'filtro-hasta' })

  const barraFiltros = el('form', { class: 'formulario', style: 'margin-bottom: 20px;' }, [
    el('div', { class: 'campo' }, [
      el('label', { for: 'filtro-usuario' }, 'Usuario'),
      filtroUsuario,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'filtro-tabla' }, 'Módulo'),
      filtroTabla,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'filtro-accion' }, 'Acción'),
      filtroAccion,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'filtro-desde' }, 'Desde'),
      filtroDesde,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'filtro-hasta' }, 'Hasta'),
      filtroHasta,
    ]),
  ])
  contenedor.append(barraFiltros)

  // Tabla
  const tabla = el('table', { class: 'tabla' }, [
    el('thead', {}, el('tr', {}, [
      el('th', {}, 'Fecha y hora'),
      el('th', {}, 'Usuario'),
      el('th', {}, 'Acción'),
      el('th', {}, 'Módulo'),
      el('th', {}, 'Detalle'),
      el('th', {}, 'IP'),
    ])),
    el('tbody', { id: 'tabla-bitacora' }, el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px;' }, 'Cargando...'))),
  ])
  const contenedorTabla = el('div', { class: 'tabla-contenedor' }, tabla)
  contenedor.append(contenedorTabla)

  // Paginación
  const paginacion = el('div', { id: 'paginacion', style: 'display: flex; gap: 10px; justify-content: center; margin-top: 20px; align-items: center;' })
  contenedor.append(paginacion)

  let paginaActual = 1
  const porPagina = 25

  async function cargarDatos() {
    const usuario = filtroUsuario.value
    const tabla = filtroTabla.value
    const accion = filtroAccion.value
    const desde = filtroDesde.value
    const hasta = filtroHasta.value

    try {
      const respuesta = await listarBitacora({
        usuario, tabla, accion, desde, hasta,
        pagina: paginaActual,
        por_pagina: porPagina,
      })

      const tbody = document.querySelector('#tabla-bitacora')
      if (respuesta.bitacora.length === 0) {
        tbody.replaceChildren(
          el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px; color: var(--suave);' }, 'No hay registros'))
        )
      } else {
        tbody.replaceChildren(...respuesta.bitacora.map(log => {
          const fecha = new Date(log.fecha).toLocaleString('es-ES')
          return el('tr', {}, [
            el('td', {}, fecha),
            el('td', {}, log.username || '(sistema)'),
            el('td', {}, el('span', { class: obtenerClaseAccion(log.accion) }, log.accion)),
            el('td', {}, log.tabla),
            el('td', { style: 'max-width: 150px; white-space: normal; word-break: break-word;' }, log.detalle || ''),
            el('td', { style: 'font-size: 12px; color: var(--suave);' }, log.ip || ''),
          ])
        }))
      }

      // Paginación
      const totalPaginas = respuesta.total_paginas
      paginacion.replaceChildren(
        paginaActual > 1 ? el('button', { class: 'btn secundario', onclick: () => { paginaActual = 1; cargarDatos() } }, '« Primera') : null,
        paginaActual > 1 ? el('button', { class: 'btn secundario', onclick: () => { paginaActual--; cargarDatos() } }, '‹ Anterior') : null,
        el('span', { style: 'padding: 0 12px; font-size: 14px; color: var(--suave);' }, `Página ${paginaActual} de ${totalPaginas}`),
        paginaActual < totalPaginas ? el('button', { class: 'btn secundario', onclick: () => { paginaActual++; cargarDatos() } }, 'Siguiente ›') : null,
        paginaActual < totalPaginas ? el('button', { class: 'btn secundario', onclick: () => { paginaActual = totalPaginas; cargarDatos() } }, 'Última »') : null,
      )
    } catch (error) {
      document.querySelector('#tabla-bitacora').replaceChildren(
        el('tr', {}, el('td', { colSpan: '6', style: 'text-align: center; padding: 20px; color: var(--error);' }, `Error: ${error.message}`))
      )
    }
  }

  // Event listeners
  filtroUsuario.addEventListener('change', () => { paginaActual = 1; cargarDatos() })
  filtroTabla.addEventListener('change', () => { paginaActual = 1; cargarDatos() })
  filtroAccion.addEventListener('change', () => { paginaActual = 1; cargarDatos() })
  filtroDesde.addEventListener('change', () => { paginaActual = 1; cargarDatos() })
  filtroHasta.addEventListener('change', () => { paginaActual = 1; cargarDatos() })

  document.querySelector('#btn-exportar').addEventListener('click', () => {
    exportarBitacoraCsv({
      usuario: filtroUsuario.value,
      tabla: filtroTabla.value,
      accion: filtroAccion.value,
      desde: filtroDesde.value,
      hasta: filtroHasta.value,
    })
  })

  cargarDatos()
}

function obtenerClaseAccion(accion) {
  if (accion.includes('CREAR') || accion.includes('INICIO')) return 'estado ok'
  if (accion.includes('FALLIDO') || accion.includes('BLOQUEO') || accion.includes('CANCELAR')) return 'estado error'
  return 'estado'
}
// ---- BITACORA: fin ----
