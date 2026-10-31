/**
 * Modal: ventana centrada, fondo semitransparente, cierre con Esc/click fuera/botón X
 * Uso: mostrarModal({ titulo, contenido, botones, primerFoco })
 */
import { el } from './comunes.js'
import { icono } from './iconos.js'

export function mostrarModal({ titulo = '', contenido = null, botones = [], primerFoco = null }) {
  // Overlay (fondo oscuro semitransparente)
  const overlay = el('div', {
    class: 'modal-overlay',
    style: 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;'
  })

  // Modal (tarjeta blanca)
  const modal = el('div', {
    class: 'modal-content',
    style: 'background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 0; position: relative;'
  })

  // Encabezado con título y botón X
  const btnCerrar = el('button', {
    class: 'modal-close',
    type: 'button',
    style: 'position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: var(--suave);'
  })
  btnCerrar.append(icono.cerrar(20))

  const header = el('div', {
    style: 'padding: 24px 24px 16px; border-bottom: 1px solid var(--borde); display: flex; justify-content: space-between; align-items: center;'
  }, [
    el('h2', { style: 'margin: 0; font-size: 18px; color: var(--texto);' }, titulo),
    btnCerrar
  ])
  modal.append(header)

  // Contenido
  const cuerpo = el('div', { style: 'padding: 24px;' })
  if (contenido) {
    if (typeof contenido === 'string') {
      cuerpo.textContent = contenido
    } else {
      cuerpo.append(contenido)
    }
  }
  modal.append(cuerpo)

  // Botones
  if (botones.length > 0) {
    const piePagina = el('div', {
      style: 'padding: 16px 24px; border-top: 1px solid var(--borde); display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;'
    })
    piePagina.append(...botones)
    modal.append(piePagina)
  }

  overlay.append(modal)

  // Funciones de cierre
  function cerrar() {
    overlay.remove()
    document.body.style.overflow = ''
  }

  btnCerrar.addEventListener('click', cerrar)
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) cerrar()
  })

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.body.contains(overlay)) cerrar()
  })

  // Agregar al DOM
  document.body.append(overlay)
  document.body.style.overflow = 'hidden'

  // Enfocar primer campo
  if (primerFoco) {
    setTimeout(() => primerFoco.focus(), 0)
  }

  return { cerrar, overlay }
}
