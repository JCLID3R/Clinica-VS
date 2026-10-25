/** Layout: menú lateral con los paquetes y casos de uso, encabezado y área de contenido. */
import { PAQUETES } from '../mapaCasosUso.js'
import { NOMBRE_SISTEMA } from '../config.js'
import { obtenerUsuario } from '../auth/sesion.js'
import { el } from '../../componentes/comunes.js'

export default function LayoutPrincipal(raiz, rutaActual) {
  const usuario = obtenerUsuario()
  raiz.replaceChildren()
  const menu = el('nav', { class: 'menu' }, [
    el('a', { class: 'marca', href: '#/' }, [el('span', { class: 'cruz' }, '✚'), NOMBRE_SISTEMA]),
    ...PAQUETES.map((p) =>
      el('div', { class: 'grupo' }, [
        el('h6', {}, p.titulo),
        ...p.casos.map((c) =>
          el('a', { href: '#' + c.ruta, class: c.ruta === rutaActual ? 'activo' : '' }, [el('b', {}, c.codigo), ' ', c.nombre]),
        ),
      ]),
    ),
  ])
  const contenido = el('main', { class: 'contenido' })
  const cabecera = el('header', { class: 'cabecera' }, [
    el('span', {}, 'Sistema web de gestión de citas médicas'),
    el('span', { class: 'usuario' }, usuario ? `${usuario.username} · ${usuario.rol}` : 'Sin sesión (CU01 pendiente)'),
  ])
  raiz.append(el('div', { class: 'app' }, [menu, el('div', { class: 'principal' }, [cabecera, contenido])]))
  return contenido
}
