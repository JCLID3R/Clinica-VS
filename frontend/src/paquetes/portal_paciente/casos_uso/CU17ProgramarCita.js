import { el, aviso } from '../../../componentes/comunes.js'
import { ErrorApi } from '../../../nucleo/api.js'
import { listarEspecialidades, medicosPorEspecialidad, horariosDisponibles, reservarCita } from '../api.js'
import { SelectorFichas } from '../../agenda/componentes/SelectorFichas.js'

export default function CU17ProgramarCita(contenedor) {
  PantallaCU17(contenedor)
}

function PantallaCU17(contenedor) {
  let paso = 1
  let datos = { especialidad: null, medico: null, fecha: null, ficha: null }

  function crearIndicadorPasos() {
    const etapas = [
      { num: 1, label: 'Especialidad' },
      { num: 2, label: 'Médico' },
      { num: 3, label: 'Fecha y ficha' },
      { num: 4, label: 'Confirmar' }
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
    const selectEspecialidad = el('select')
    selectEspecialidad.appendChild(el('option', { value: '' }, 'Selecciona especialidad...'))

    async function cargarEspecialidades() {
      try {
        const res = await listarEspecialidades()
        res.especialidades.forEach((esp) => {
          selectEspecialidad.appendChild(el('option', { value: esp.id_especialidad }, esp.nombre))
        })
      } catch (error) {
        aviso('Error al cargar especialidades', 'error')
      }
    }

    cargarEspecialidades()

    selectEspecialidad.addEventListener('change', () => {
      if (selectEspecialidad.value) {
        datos.especialidad = { id_especialidad: selectEspecialidad.value, nombre: selectEspecialidad.options[selectEspecialidad.selectedIndex].text }
        mostrarPaso2()
      }
    })

    contenedor.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita'),
      el('div', { class: 'campo' }, [el('label', {}, 'Especialidad'), selectEspecialidad])
    )
  }

  async function mostrarPaso2() {
    paso = 2
    const selectMedico = el('select')
    selectMedico.appendChild(el('option', { value: '' }, 'Cargando médicos...'))

    try {
      const res = await medicosPorEspecialidad(datos.especialidad.id_especialidad)
      selectMedico.replaceChildren(el('option', { value: '' }, 'Selecciona médico...'))
      res.medicos.forEach((medico) => {
        selectMedico.appendChild(el('option', { value: medico.id_medico }, `${medico.nombres} ${medico.apellidos}`))
      })
    } catch (error) {
      aviso('Error al cargar médicos', 'error')
      selectMedico.replaceChildren(el('option', { value: '' }, 'Error al cargar'))
    }

    selectMedico.addEventListener('change', () => {
      if (selectMedico.value) {
        const medico = Array.from(selectMedico.options).find(o => o.value === selectMedico.value)
        datos.medico = { id_medico: parseInt(selectMedico.value), nombre: medico.text }
        mostrarPaso3()
      }
    })

    contenedor.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita'),
      el('div', { class: 'campo' }, [el('label', {}, 'Especialidad'), el('div', {}, datos.especialidad.nombre)]),
      el('div', { class: 'campo' }, [el('label', {}, 'Médico'), selectMedico])
    )
  }

  async function mostrarPaso3() {
    paso = 3
    const inputFecha = el('input', { type: 'date', id: 'fecha-cita' })
    const contenedorFichas = el('div', { id: 'contenedor-fichas' }, el('p', {}, 'Selecciona una fecha'))

    inputFecha.addEventListener('change', () => {
      cargarFichas(inputFecha.value)
    })

    async function cargarFichas(fecha) {
      if (!fecha) return
      contenedorFichas.replaceChildren(el('p', {}, 'Cargando fichas...'))

      try {
        const res = await horariosDisponibles(datos.especialidad.id_especialidad, datos.medico.id_medico, fecha)
        if (!res.medicos || res.medicos.length === 0) {
          contenedorFichas.replaceChildren(el('p', {}, 'No hay fichas disponibles'))
          return
        }

        const selector = SelectorFichas(res)
        contenedorFichas.replaceChildren(selector)

        selector.addEventListener('fichaSeleccionada', (evt) => {
          datos.fecha = fecha
          datos.ficha = evt.detail.ficha
          mostrarPaso4()
        })
      } catch (error) {
        aviso('Error al cargar fichas', 'error')
      }
    }

    contenedor.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita'),
      el('div', { class: 'campo' }, [el('label', {}, 'Especialidad'), el('div', {}, datos.especialidad.nombre)]),
      el('div', { class: 'campo' }, [el('label', {}, 'Médico'), el('div', {}, datos.medico.nombre)]),
      el('div', { class: 'campo' }, [el('label', {}, 'Fecha'), inputFecha]),
      contenedorFichas
    )
  }

  async function mostrarPaso4() {
    paso = 4
    const [año, mes, día] = datos.fecha.split('-')
    const fechaFormato = `${día}/${mes}/${año}`

    const btnConfirmar = el('button', { type: 'button', class: 'btn' }, 'Confirmar reserva')
    const btnCancelar = el('button', { type: 'button', class: 'btn secundario' }, 'Volver')

    btnConfirmar.addEventListener('click', async () => {
      try {
        const res = await reservarCita(datos.especialidad.id_especialidad, datos.medico.id_medico, datos.fecha, datos.ficha.hora, datos.ficha.hora_fin)
        mostrarComprobante(res.comprobante, contenedor)
      } catch (error) {
        const msg = error instanceof ErrorApi ? error.mensaje : 'Error al reservar'
        aviso(msg, 'error')
      }
    })

    btnCancelar.addEventListener('click', mostrarPaso3)

    contenedor.replaceChildren(
      crearIndicadorPasos(),
      el('h2', { class: 'encabezado-pagina' }, 'Programar cita'),
      el('div', { class: 'info-compacta' }, [
        el('p', {}, [el('strong', {}, 'Especialidad: '), datos.especialidad.nombre]),
        el('p', {}, [el('strong', {}, 'Médico: '), datos.medico.nombre]),
        el('p', {}, [el('strong', {}, 'Fecha: '), `${fechaFormato} ${datos.ficha.hora}`]),
      ]),
      el('div', { class: 'acciones' }, [btnConfirmar, btnCancelar])
    )
  }

  mostrarPaso1()

  function mostrarComprobante(comprobante, contenedor) {
    const btnVerCitas = el('a', { href: '#/portal/mis-citas', class: 'btn' }, 'Ver mis citas')
    const btnOtra = el('button', { type: 'button', class: 'btn secundario' }, 'Programar otra')
    btnOtra.addEventListener('click', () => {
      paso = 1
      datos = { especialidad: null, medico: null, fecha: null, ficha: null }
      mostrarPaso1()
    })

    contenedor.replaceChildren(
      el('h2', { class: 'encabezado-pagina' }, 'Cita reservada'),
      el('div', { class: 'comprobante' }, [
        el('p', {}, [el('strong', {}, 'Código: '), comprobante.codigo]),
        el('p', {}, [el('strong', {}, 'Paciente: '), comprobante.paciente]),
        el('p', {}, [el('strong', {}, 'Especialidad: '), comprobante.especialidad]),
        el('p', {}, [el('strong', {}, 'Médico: '), comprobante.medico]),
        el('p', {}, [el('strong', {}, 'Fecha: '), comprobante.fecha]),
        el('p', {}, [el('strong', {}, 'Horario: '), comprobante.horario]),
      ]),
      el('div', { class: 'acciones' }, [btnVerCitas, btnOtra])
    )
  }
}
