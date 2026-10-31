/**
 * CU08 - Consultar disponibilidad de citas  (paquete agenda, Ciclo 1)
 * Actores: Recepcionista, Paciente (Ciclo 2)
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js.
 * Estado: IMPLEMENTADO.
 */
import { el } from '../../../componentes/comunes.js'
import { listarEspecialidades } from '../../personas/api.js'
import { listarMedicosPorEspecialidad, consultarDisponibilidad } from '../api.js'
import { SelectorFichas } from '../componentes/SelectorFichas.js'

export default function CU08ConsultarDisponibilidad(pantalla) {
  const ui = crearUI()
  pantalla.replaceChildren(ui.pantalla)

  let especialidadesCache = []

  listarEspecialidades(1, 100).then((res) => {
    especialidadesCache = res.especialidades || []
    ui.selectEspecialidad.innerHTML = ''
    ui.selectEspecialidad.appendChild(el('option', { value: '' }, 'Selecciona especialidad...'))
    especialidadesCache.forEach((esp) => {
      ui.selectEspecialidad.appendChild(el('option', { value: esp.id_especialidad }, esp.nombre))
    })
  })

  ui.selectEspecialidad.addEventListener('change', async () => {
    const idEsp = ui.selectEspecialidad.value
    ui.selectMedico.innerHTML = ''
    ui.selectMedico.appendChild(el('option', { value: '' }, 'Cualquier médico'))
    ui.selectFecha.value = ''
    ui.contenedorFichas.replaceChildren()

    if (idEsp) {
      try {
        const res = await listarMedicosPorEspecialidad(idEsp)
        res.medicos.forEach((med) => {
          ui.selectMedico.appendChild(
            el('option', { value: med.id_medico }, `${med.nombres} ${med.apellidos}`)
          )
        })
      } catch (error) {
        ui.contenedorFichas.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
      }
    }
  })

  ui.selectFecha.addEventListener('change', cargarFichas)
  ui.selectMedico.addEventListener('change', cargarFichas)

  async function cargarFichas() {
    const idEsp = ui.selectEspecialidad.value
    const fecha = ui.selectFecha.value

    if (!idEsp || !fecha) {
      ui.contenedorFichas.replaceChildren()
      return
    }

    ui.contenedorFichas.replaceChildren(el('p', {}, 'Cargando...'))

    try {
      const params = { id_especialidad: idEsp, fecha }
      if (ui.selectMedico.value) {
        params.id_medico = ui.selectMedico.value
      }

      const res = await consultarDisponibilidad(params)
      const selector = SelectorFichas(res, true)
      ui.contenedorFichas.replaceChildren(selector)
    } catch (error) {
      ui.contenedorFichas.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
    }
  }
}

function crearUI() {
  const hoy = new Date().toISOString().split('T')[0]
  const max30 = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]

  const selectEspecialidad = el('select', { id: 'cu08-especialidad' })
  const selectMedico = el('select', { id: 'cu08-medico' })
  const selectFecha = el('input', { type: 'date', id: 'cu08-fecha', min: hoy, max: max30 })

  const contenedorFichas = el('div', { id: 'cu08-fichas' })

  const pantalla = el('div', { class: 'pantalla cu08' }, [
    el('h2', { class: 'encabezado-pagina' }, 'Fichas disponibles'),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu08-especialidad' }, 'Especialidad'),
      selectEspecialidad,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu08-medico' }, 'Médico'),
      selectMedico,
    ]),
    el('div', { class: 'campo' }, [
      el('label', { for: 'cu08-fecha' }, 'Fecha'),
      selectFecha,
    ]),
    contenedorFichas,
  ])

  return { pantalla, selectEspecialidad, selectMedico, selectFecha, contenedorFichas }
}
