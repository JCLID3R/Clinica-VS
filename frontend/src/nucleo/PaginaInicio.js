/** Página de inicio: resumen de paquetes y estado de la API. */
import { PAQUETES } from './mapaCasosUso.js'
import { api } from './api.js'
import { el } from '../componentes/comunes.js'

export default function PaginaInicio(contenedor) {
  const estado = el('p', { class: 'estado' }, 'Comprobando conexión con la API…')
  contenedor.append(
    el('h1', {}, 'Clínica Vida Salud'),
    el('p', {}, 'Sistema web de gestión de citas médicas. 6 paquetes y 17 casos de uso (todos pendientes de implementar).'),
    estado,
    el('div', { class: 'tarjetas' }, PAQUETES.map((p) =>
      el('div', { class: 'tarjeta' }, [el('h3', {}, p.titulo), el('p', {}, `${p.casos.length} casos de uso`),
        el('ul', {}, p.casos.map((c) => el('li', {}, [el('a', { href: '#' + c.ruta }, `${c.codigo} ${c.nombre}`)])))]),
    )),
  )
  api.get('/salud/bd')
    .then((r) => { estado.textContent = `API conectada · base de datos ${r.base} (${r.postgres})`; estado.classList.add('ok') })
    .catch((e) => { estado.textContent = `Sin conexión con la API: ${e.message}`; estado.classList.add('error') })
}
