/**
 * Layout: menú lateral con los paquetes y casos de uso, encabezado y área de contenido.
 * - El botón ☰ de la esquina superior izquierda oculta o muestra el menú lateral.
 * - En computadora el menú se oculta hacia la izquierda y el contenido ocupa todo el ancho;
 *   la preferencia se recuerda en el navegador.
 * - En celular o tablet (hasta 900 px) el menú empieza oculto y se abre encima del contenido;
 *   se cierra con el botón, tocando fuera, con la tecla Esc o al elegir una opción.
 */
import { PAQUETES } from '../mapaCasosUso.js'
import { NOMBRE_SISTEMA } from '../config.js'
import { obtenerUsuario } from '../auth/sesion.js'
import { el } from '../../componentes/comunes.js'

const CLAVE_MENU = 'clinica.menuOculto'
const pantallaChica = window.matchMedia('(max-width: 900px)')

function leerPreferencia() {
  try { return localStorage.getItem(CLAVE_MENU) === '1' } catch { return false }
}

function guardarPreferencia(oculto) {
  try { localStorage.setItem(CLAVE_MENU, oculto ? '1' : '0') } catch { /* sin almacenamiento: solo dura la visita */ }
}

export default function LayoutPrincipal(raiz, rutaActual) {
  const usuario = obtenerUsuario()
  raiz.replaceChildren()

  const cerrar = el('button', { class: 'btn-cerrar-menu', type: 'button', 'aria-label': 'Cerrar el menú' }, '✕')
  const menu = el('nav', { class: 'menu', id: 'menu-lateral', 'aria-label': 'Menú principal' }, [
    el('div', { class: 'menu-cabeza' }, [
      el('a', { class: 'marca', href: '#/' }, [el('span', { class: 'cruz' }, '✚'), NOMBRE_SISTEMA]),
      cerrar,
    ]),
    ...PAQUETES.map((p) =>
      el('div', { class: 'grupo' }, [
        el('h6', {}, p.titulo),
        ...p.casos.map((c) =>
          el('a', { href: '#' + c.ruta, class: c.ruta === rutaActual ? 'activo' : '' }, [el('b', {}, c.codigo), ' ', c.nombre]),
        ),
      ]),
    ),
  ])

  const app = el('div', { class: 'app' })
  const velo = el('div', { class: 'velo', 'aria-hidden': 'true' })
  const boton = el('button', {
    class: 'btn-menu', type: 'button', 'aria-controls': 'menu-lateral', 'aria-label': 'Mostrar u ocultar el menú',
  }, [el('span'), el('span'), el('span')])

  // En pantalla chica el menú abierto es temporal; en computadora se respeta la preferencia guardada.
  function aplicar(oculto) {
    app.classList.toggle('menu-oculto', oculto)
    boton.setAttribute('aria-expanded', String(!oculto))
  }
  const estaOculto = () => app.classList.contains('menu-oculto')
  aplicar(pantallaChica.matches ? true : leerPreferencia())

  boton.addEventListener('click', () => {
    const oculto = !estaOculto()
    aplicar(oculto)
    if (!pantallaChica.matches) guardarPreferencia(oculto)
  })
  velo.addEventListener('click', () => aplicar(true))
  cerrar.addEventListener('click', () => aplicar(true))
  menu.addEventListener('click', (e) => { if (pantallaChica.matches && e.target.closest('a')) aplicar(true) })
  document.onkeydown = (e) => { if (e.key === 'Escape' && pantallaChica.matches && !estaOculto()) aplicar(true) }
  pantallaChica.onchange = (e) => aplicar(e.matches ? true : leerPreferencia())

  const contenido = el('main', { class: 'contenido' })
  const cabecera = el('header', { class: 'cabecera' }, [
    boton,
    el('span', { class: 'titulo' }, 'Sistema web de gestión de citas médicas'),
    el('span', { class: 'usuario' }, usuario ? `${usuario.username} · ${usuario.rol}` : 'Sin sesión (CU01 pendiente)'),
  ])

  app.append(menu, velo, el('div', { class: 'principal' }, [cabecera, contenido]))
  ajustarLayoutCU01(app, cabecera, rutaActual)
  raiz.append(app)
  return contenido
}

// ---- CU01: inicio ----
import { PERMISO_POR_CU } from '../mapaCasosUso.js'
import { tienePermiso, cerrarSesion } from '../auth/sesion.js'

const CU_POR_RUTA = Object.fromEntries(PAQUETES.flatMap((p) => p.casos.map((c) => ['#' + c.ruta, c.codigo])))

/**
 * Ajusta el layout según la sesión:
 *   - sin sesión: pantalla completa de inicio de sesión (sin menú ni cabecera);
 *   - con cambio de contraseña pendiente: sin menú, solo el formulario y «Cerrar sesión»;
 *   - con sesión: el menú muestra solo los casos de uso que permite el rol.
 */
function ajustarLayoutCU01(app, cabecera, rutaActual) {
  const usuario = obtenerUsuario()
  if (!usuario) {
    app.classList.add('sin-sesion')
    return
  }
  if (usuario.debe_cambiar_contrasena) app.classList.add('cambio-obligatorio')

  app.querySelectorAll('.menu .grupo a').forEach((enlace) => {
    const permiso = PERMISO_POR_CU[CU_POR_RUTA[enlace.getAttribute('href')]]
    if (permiso && !tienePermiso(permiso)) enlace.remove()
  })
  app.querySelectorAll('.menu .grupo').forEach((grupo) => { if (!grupo.querySelector('a')) grupo.remove() })
  const cuenta = app.querySelector('.menu a[href="#/login"]')
  if (cuenta) cuenta.lastChild.textContent = 'Mi cuenta y contraseña'
  if (rutaActual === '/login' && cuenta) cuenta.classList.add('activo')

  const etiqueta = cabecera.querySelector('.usuario')
  etiqueta.textContent = `${usuario.username} · ${usuario.rol}`
  const salir = el('button', { class: 'btn secundario btn-salir', type: 'button' }, 'Cerrar sesión')
  salir.addEventListener('click', () => { salir.disabled = true; cerrarSesion() })
  cabecera.append(salir)
}
// ---- CU01: fin ----
