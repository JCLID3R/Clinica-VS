/**
 * Componente reutilizable: Selector de fichas disponibles (usado en CU08 y CU09).
 * Muestra fichas agrupadas por médico y turno, con colores para disponibilidad.
 * Emit: 'fichaSeleccionada' con { medico, turno, ficha } (si no es soloLectura)
 */
import { el } from '../../../componentes/comunes.js'

export function SelectorFichas(datos, soloLectura = false) {
  const contenedor = el('div', { class: 'selector-fichas' })
  if (!datos || !datos.medicos || datos.medicos.length === 0) {
    contenedor.appendChild(el('p', {}, 'No hay fichas disponibles para la fecha y especialidad seleccionadas.'))
    return contenedor
  }

  for (const medico of datos.medicos) {
    const seccionMedico = el('div', { class: 'seccion-medico' })
    const nombreMedico = el('h4', {}, `${medico.nombres} ${medico.apellidos} (${medico.registro_profesional})`)
    seccionMedico.appendChild(nombreMedico)

    for (const [turno, fichas] of Object.entries(medico.turnos)) {
      const seccionTurno = el('div', { class: 'seccion-turno' })
      const tituloTurno = el('h5', {}, `${turno}`)
      seccionTurno.appendChild(tituloTurno)

      const gridFichas = el('div', { class: 'grid-fichas' })
      for (const ficha of fichas) {
        const boton = el('button', {
          class: `ficha ${ficha.disponible ? 'disponible' : 'ocupada'}`,
          type: 'button',
        }, `${ficha.numero} (${ficha.hora})`)

        if (ficha.disponible && !soloLectura) {
          boton.addEventListener('click', () => {
            contenedor.dispatchEvent(
              new CustomEvent('fichaSeleccionada', {
                detail: { medico, turno, ficha },
                bubbles: true,
              })
            )
          })
        } else if (!ficha.disponible || soloLectura) {
          boton.disabled = true
        }

        gridFichas.appendChild(boton)
      }
      seccionTurno.appendChild(gridFichas)
      seccionMedico.appendChild(seccionTurno)
    }

    contenedor.appendChild(seccionMedico)
  }

  return contenedor
}

export default SelectorFichas
