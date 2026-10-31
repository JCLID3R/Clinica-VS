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
// ---- REDISENO: inicio ----
import { icono } from '../../componentes/iconos.js'
// ---- REDISENO: fin ----

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

  // ---- REDISENO: inicio ----
  const cerrar = el('button', { class: 'btn-cerrar-menu', type: 'button', 'aria-label': 'Cerrar el menú' })
  cerrar.append(icono.cerrar())

  const secciones = {
    'Inicio': [{ titulo: 'Panel principal', ruta: '/', iconoFn: () => icono.panelPrincipal() }],
    'Citas': [
      { titulo: 'Programar cita', ruta: '/citas/programar', iconoFn: () => icono.programarCita() },
      { titulo: 'Agenda médica', ruta: '/citas/agenda', iconoFn: () => icono.agendaMedica() },
    ],
    'Registros': [
      { titulo: 'Pacientes', ruta: '/personas/pacientes', iconoFn: () => icono.pacientes() },
      { titulo: 'Médicos', ruta: '/personas/medicos', iconoFn: () => icono.medicos() },
      { titulo: 'Especialidades', ruta: '/personas/especialidades', iconoFn: () => icono.especialidades() },
      { titulo: 'Horarios', ruta: '/agenda/horarios', iconoFn: () => icono.horarios() },
    ],
    'Administración': [
      { titulo: 'Usuarios', ruta: '/seguridad/usuarios', iconoFn: () => icono.usuarios() },
      { titulo: 'Roles y permisos', ruta: '/seguridad/roles', iconoFn: () => icono.rolesPermisos() },
      { titulo: 'Bitácora', ruta: '/seguridad/bitacora', iconoFn: () => icono.bitacora() },
    ],
  }

  const menuGrupos = Object.entries(secciones).map(([seccion, items]) =>
    el('div', { class: 'grupo' }, [
      el('h6', {}, seccion),
      ...items.map((item) => {
        const cu = PAQUETES.flatMap(p => p.casos).find(c => c.ruta === item.ruta)
        return el('a', {
          href: '#' + item.ruta,
          class: item.ruta === rutaActual ? 'activo' : '',
          title: cu ? cu.codigo : '',
        }, [
          item.iconoFn(),
          item.titulo,
        ])
      }),
    ]),
  )

  const logoDiv = el('span', { class: 'marca-icono' })
  logoDiv.append(icono.logo())

  const menu = el('nav', { class: 'menu', id: 'menu-lateral', 'aria-label': 'Menú principal' }, [
    el('div', { class: 'menu-cabeza' }, [
      el('a', { class: 'marca', href: '#/' }, [
        logoDiv,
        el('div', {}, [
          el('div', {}, 'Vida Salud'),
          el('div', { class: 'marca-subtitulo' }, 'Clínica · Santa Cruz'),
        ]),
      ]),
      cerrar,
    ]),
    ...menuGrupos,
  ])
  // ---- REDISENO: fin ----

  const app = el('div', { class: 'app' })
  const velo = el('div', { class: 'velo', 'aria-hidden': 'true' })

  // Crear btnHamburguesa aquí para usarlo en aplicar()
  const btnHamburguesa = el('button', {
    class: 'btn-menu', type: 'button', 'aria-controls': 'menu-lateral', 'aria-label': 'Mostrar u ocultar el menú',
  })
  btnHamburguesa.append(icono.hamburguesa(20))

  // En pantalla chica el menú abierto es temporal; en computadora se respeta la preferencia guardada.
  function aplicar(oculto) {
    app.classList.toggle('menu-oculto', oculto)
    btnHamburguesa.setAttribute('aria-expanded', String(!oculto))
  }
  const estaOculto = () => app.classList.contains('menu-oculto')
  aplicar(pantallaChica.matches ? true : leerPreferencia())

  btnHamburguesa.addEventListener('click', () => {
    const oculto = !estaOculto()
    aplicar(oculto)
    if (!pantallaChica.matches) guardarPreferencia(oculto)
  })
  velo.addEventListener('click', () => aplicar(true))
  cerrar.addEventListener('click', () => aplicar(true))
  menu.addEventListener('click', (e) => { if (pantallaChica.matches && e.target.closest('a')) aplicar(true) })
  document.onkeydown = (e) => { if (e.key === 'Escape' && pantallaChica.matches && !estaOculto()) aplicar(true) }
  pantallaChica.onchange = (e) => aplicar(e.matches ? true : leerPreferencia())

  // ---- REDISENO: inicio ----
  const contenido = el('main', { class: 'contenido' })

  // Fecha en español
  function obtenerFechaLarga() {
    const ahora = new Date()
    const diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
    const dia = diasSemana[ahora.getDay()]
    const fecha = ahora.getDate()
    const mes = meses[ahora.getMonth()]
    const año = ahora.getFullYear()
    return `${dia} ${fecha} de ${mes} de ${año}`
  }

  const fechaSpan = el('span', { class: 'fecha' })
  fechaSpan.append(icono.calendario(16), ' ', obtenerFechaLarga())

  let menuUsuarioBotón, desplegableUsuario
  if (usuario) {
    menuUsuarioBotón = el('button', { class: 'menu-usuario', type: 'button' }, [
      el('span', { class: 'avatar' }, usuario.username[0].toUpperCase()),
      el('div', { class: 'usuario-info' }, [
        el('span', { class: 'usuario-nombre' }, usuario.username),
        el('span', { class: 'usuario-rol' }, usuario.rol),
      ]),
    ])
    const flechaSvg = el('span', { class: 'flecha' })
    flechaSvg.append(icono.flechaAbajo(16))
    menuUsuarioBotón.append(flechaSvg)

    const miCuenta = el('a', { href: '#/login' })
    miCuenta.append(icono.miCuenta(16), ' Mi cuenta y contraseña')

    const btnCerrarSesion = el('button', { type: 'button', class: 'btn-cerrar-sesion-menu' })
    btnCerrarSesion.append(icono.cerrarSesion(16), ' Cerrar sesión')

    desplegableUsuario = el('div', { class: 'desplegable-usuario' }, [
      miCuenta,
      el('div', { class: 'separador' }),
      btnCerrarSesion,
    ])
  }

  const cabecera = el('header', { class: 'cabecera' }, [
    el('div', { class: 'cabecera-izq' }, [
      btnHamburguesa,
      el('span', { class: 'titulo' }, 'Sistema de gestión de citas'),
      fechaSpan,
    ]),
    el('div', { class: 'cabecera-der' }, usuario ? [
      menuUsuarioBotón,
      desplegableUsuario,
    ] : [el('span', {}, 'Sin sesión')]),
  ])
  // ---- REDISENO: fin ----

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

  // ---- REDISENO: inicio ----
  app.querySelectorAll('.menu .grupo a').forEach((enlace) => {
    const permiso = PERMISO_POR_CU[CU_POR_RUTA[enlace.getAttribute('href')]]
    if (permiso && !tienePermiso(permiso)) enlace.remove()
  })
  app.querySelectorAll('.menu .grupo').forEach((grupo) => { if (!grupo.querySelector('a')) grupo.remove() })

  // Menú desplegable del usuario
  const menuUsuario = cabecera.querySelector('.menu-usuario')
  if (menuUsuario) {
    menuUsuario.addEventListener('click', () => menuUsuario.classList.toggle('abierto'))
    document.addEventListener('click', (e) => {
      if (!menuUsuario.contains(e.target) && !cabecera.querySelector('.desplegable-usuario').contains(e.target)) {
        menuUsuario.classList.remove('abierto')
      }
    })
  }

  const btnCerrarSesion = cabecera.querySelector('.btn-cerrar-sesion-menu')
  if (btnCerrarSesion) {
    btnCerrarSesion.addEventListener('click', () => { btnCerrarSesion.disabled = true; cerrarSesion() })
  }
  // ---- REDISENO: fin ----
}
// ---- CU01: fin ----
