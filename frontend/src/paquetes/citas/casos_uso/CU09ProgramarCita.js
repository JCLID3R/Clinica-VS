/**
 * CU09 - Programar cita médica  (paquete citas, Ciclo 1)
 * Actores: Recepcionista, Paciente (Ciclo 2)
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js.
 * Estado: IMPLEMENTADO.
 */
import { el } from '../../../componentes/comunes.js'
import { buscarPaciente } from '../../personas/api.js'
import { listarEspecialidades } from '../../personas/api.js'
import { listarMedicosPorEspecialidad, consultarDisponibilidad } from '../../agenda/api.js'
import { SelectorFichas } from '../../agenda/componentes/SelectorFichas.js'
import { programarCita } from '../api.js'

export default function CU09ProgramarCita(pantalla) {
  let paso = 1
  let datos = { paciente: null, especialidad: null, medico: null, fecha: null, ficha: null }

  function crearIndicadorPasos() {
    const etapas = [
      { num: 1, label: 'Paciente' },
      { num: 2, label: 'Especialidad' },
      { num: 3, label: 'Médico' },
      { num: 4, label: 'Fecha y ficha' },
      { num: 5, label: 'Confirmar' }
    ]

    const indicador = el('div', { class: 'indicador-pasos' })
    etapas.forEach((e, idx) => {
      const item = el('div', { class: `paso ${e.num === paso ? 'activo' : ''} ${e.num < paso ? 'completado' : ''}` })
      item.appendChild(el('span', { class: 'numero' }, String(e.num)))
      item.appendChild(el('span', { class: 'label' }, e.label))
      indicador.appendChild(item)
      if (idx < etapas.length - 1) {
        indicador.appendChild(el('div', { class: 'separador' }))
      }
    })
    return indicador
  }

  function mostrarPaso1() {
    paso = 1
    const inputBusqueda = el('input', { type: 'text', placeholder: 'Buscar por CI o nombre...' })
    const resultados = el('div', { class: 'resultados-busqueda' })
    const btnBuscar = el('button', { type: 'button', class: 'btn' }, 'Buscar')

    btnBuscar.addEventListener('click', async () => {
      const q = inputBusqueda.value.trim()
      if (!q) {
        resultados.replaceChildren(el('p', { class: 'error' }, 'Ingresa un CI o nombre'))
        return
      }

      try {
        const res = await buscarPaciente(q)
        resultados.replaceChildren()
        if (!res.pacientes || res.pacientes.length === 0) {
          resultados.appendChild(el('p', {}, 'No hay resultados'))
          return
        }

        res.pacientes.forEach((p) => {
          const tarjeta = el('div', { class: 'tarjeta-paciente' })
          const info = el('div')
          info.appendChild(el('p', {}, `${p.nombres} ${p.apellidos}`))
          info.appendChild(el('p', { class: 'suave' }, `CI: ${p.ci}`))
          tarjeta.appendChild(info)

          const btn = el('button', { type: 'button', class: 'btn' }, 'Seleccionar')
          btn.addEventListener('click', () => {
            datos.paciente = p
            mostrarPaso2()
          })
          tarjeta.appendChild(btn)
          resultados.appendChild(tarjeta)
        })
      } catch (error) {
        resultados.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
      }
    })

    pantalla.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita médica'),
      el('div', { class: 'campo' }, [el('label', {}, 'Paciente'), inputBusqueda]),
      btnBuscar,
      resultados
    )
  }

  async function mostrarPaso2() {
    paso = 2
    const selectEspecialidad = el('select')
    selectEspecialidad.appendChild(el('option', { value: '' }, 'Selecciona especialidad...'))

    try {
      const res = await listarEspecialidades(1, 100)
      res.especialidades.forEach((esp) => {
        selectEspecialidad.appendChild(el('option', { value: esp.id_especialidad }, esp.nombre))
      })
    } catch (error) {
      pantalla.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
      return
    }

    selectEspecialidad.addEventListener('change', async () => {
      if (selectEspecialidad.value) {
        datos.especialidad = parseInt(selectEspecialidad.value)
        mostrarPaso3()
      }
    })

    const btnAtras = el('button', { type: 'button', class: 'btn secundario' }, 'Atrás')
    btnAtras.addEventListener('click', mostrarPaso1)

    pantalla.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita médica'),
      el('p', { class: 'resumen-datos' }, `Paciente: ${datos.paciente.nombres} ${datos.paciente.apellidos}`),
      el('div', { class: 'campo' }, [el('label', {}, 'Especialidad'), selectEspecialidad]),
      el('div', { class: 'botones' }, [btnAtras])
    )
  }

  async function mostrarPaso3() {
    paso = 3
    const selectMedico = el('select')
    selectMedico.appendChild(el('option', { value: '' }, 'Selecciona médico...'))
    selectMedico.appendChild(el('option', { value: '0' }, 'Cualquier médico'))

    try {
      const res = await listarMedicosPorEspecialidad(datos.especialidad)
      res.medicos.forEach((m) => {
        selectMedico.appendChild(el('option', { value: m.id_medico }, `${m.nombres} ${m.apellidos}`))
      })
    } catch (error) {
      pantalla.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
      return
    }

    selectMedico.addEventListener('change', () => {
      if (selectMedico.value !== '') {
        datos.medico = parseInt(selectMedico.value) || null
        mostrarPaso4()
      }
    })

    const btnAtras = el('button', { type: 'button', class: 'btn secundario' }, 'Atrás')
    btnAtras.addEventListener('click', mostrarPaso2)

    pantalla.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita médica'),
      el('p', { class: 'resumen-datos' }, `Paciente: ${datos.paciente.nombres} ${datos.paciente.apellidos}`),
      el('div', { class: 'campo' }, [el('label', {}, 'Médico'), selectMedico]),
      el('div', { class: 'botones' }, [btnAtras])
    )
  }

  async function mostrarPaso4() {
    paso = 4
    const hoy = new Date().toISOString().split('T')[0]
    const max30 = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]

    const selectFecha = el('input', { type: 'date', min: hoy, max: max30 })
    const contenedorFichas = el('div', { id: 'cu09-fichas' })
    const btnCargar = el('button', { type: 'button', class: 'btn' }, 'Cargar fichas')
    const btnAtras = el('button', { type: 'button', class: 'btn secundario' }, 'Atrás')

    btnCargar.addEventListener('click', async () => {
      const fecha = selectFecha.value
      if (!fecha) {
        contenedorFichas.replaceChildren(el('p', { class: 'error' }, 'Selecciona una fecha'))
        return
      }

      try {
        const res = await consultarDisponibilidad({
          id_especialidad: datos.especialidad,
          id_medico: datos.medico || undefined,
          fecha
        })
        if (!res.medicos || res.medicos.length === 0) {
          contenedorFichas.replaceChildren(el('p', { class: 'error' }, 'No hay disponibilidad ese día'))
          return
        }
        const selector = SelectorFichas(res)
        selector.addEventListener('fichaSeleccionada', (evt) => {
          datos.fecha = fecha
          datos.ficha = evt.detail.ficha
          mostrarPaso5()
        })
        contenedorFichas.replaceChildren(selector)
      } catch (error) {
        contenedorFichas.replaceChildren(el('p', { class: 'error' }, 'Error: ' + error.message))
      }
    })

    btnAtras.addEventListener('click', mostrarPaso3)

    pantalla.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita médica'),
      el('p', { class: 'resumen-datos' }, `Paciente: ${datos.paciente.nombres} ${datos.paciente.apellidos}`),
      el('div', { class: 'campo' }, [el('label', {}, 'Fecha'), selectFecha]),
      btnCargar,
      contenedorFichas,
      el('div', { class: 'botones' }, [btnAtras])
    )
  }

  async function mostrarPaso5() {
    paso = 5
    const selectModalidad = el('select')
    selectModalidad.innerHTML = '<option value="presencial">Presencial</option><option value="virtual">Virtual</option>'

    const inputMotivo = el('textarea', { placeholder: 'Motivo (opcional)' })
    const btnConfirmar = el('button', { type: 'button', class: 'btn' }, 'Confirmar')
    const btnAtras = el('button', { type: 'button', class: 'btn secundario' }, 'Atrás')

    btnConfirmar.addEventListener('click', async () => {
      btnConfirmar.disabled = true
      try {
        const res = await programarCita({
          id_paciente: datos.paciente.id_paciente,
          id_medico: datos.medico,
          id_especialidad: datos.especialidad,
          fecha: datos.fecha,
          hora_inicio: datos.ficha.hora,
          hora_fin: datos.ficha.hora_fin,
          modalidad: selectModalidad.value,
          motivo: inputMotivo.value.trim() || null,
        })

        const btnNueva = el('button', { type: 'button', class: 'btn' }, 'Programar otra cita')
        btnNueva.addEventListener('click', () => {
          paso = 1
          datos = { paciente: null, especialidad: null, medico: null, fecha: null, ficha: null }
          mostrarPaso1()
        })

        pantalla.replaceChildren(
          el('h2', { class: 'encabezado-pagina' }, 'Cita programada'),
          el('div', { class: 'comprobante' }, [
            el('p', {}, [el('strong', {}, 'Código: '), res.comprobante.codigo]),
            el('p', {}, [el('strong', {}, 'Paciente: '), res.comprobante.paciente]),
            el('p', {}, [el('strong', {}, 'Especialidad: '), res.comprobante.especialidad]),
            el('p', {}, [el('strong', {}, 'Médico: '), res.comprobante.medico]),
            el('p', {}, [el('strong', {}, 'Fecha: '), res.comprobante.fecha]),
            el('p', {}, [el('strong', {}, 'Horario: '), res.comprobante.horario]),
            res.comprobante.modalidad && el('p', {}, [el('strong', {}, 'Modalidad: '), res.comprobante.modalidad]),
            res.comprobante.motivo && el('p', {}, [el('strong', {}, 'Motivo: '), res.comprobante.motivo]),
          ]),
          btnNueva
        )
      } catch (error) {
        const msg = error.message || 'Error desconocido'
        const btnReintentar = el('button', { type: 'button', class: 'btn secundario' }, 'Atrás')
        btnReintentar.addEventListener('click', mostrarPaso5)
        pantalla.replaceChildren(
          el('p', { class: 'error' }, 'Error: ' + msg),
          btnReintentar
        )
        btnConfirmar.disabled = false
      }
    })

    btnAtras.addEventListener('click', mostrarPaso4)

    pantalla.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Confirmar cita'),
      el('p', { class: 'resumen-datos' }, `Paciente: ${datos.paciente.nombres} ${datos.paciente.apellidos}`),
      el('p', { class: 'resumen-datos' }, `Fecha: ${datos.fecha}, Ficha: ${datos.ficha.numero} (${datos.ficha.hora})`),
      el('div', { class: 'campo' }, [el('label', {}, 'Modalidad'), selectModalidad]),
      el('div', { class: 'campo' }, [el('label', {}, 'Motivo'), inputMotivo]),
      el('div', { class: 'botones' }, [btnConfirmar, btnAtras])
    )
  }

  mostrarPaso1()
}
