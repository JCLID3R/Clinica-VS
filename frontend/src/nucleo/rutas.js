/**
 * Enrutador del frontend con rutas tipo #/paquete/pantalla.
 * Junta las rutas de los 6 paquetes (cada paquete tiene su propio rutas.js).
 */
import LayoutPrincipal from './layout/LayoutPrincipal.js'
import PaginaInicio from './PaginaInicio.js'
import { puedeEntrar } from './auth/rutaProtegida.js'
import seguridad from '../paquetes/seguridad/rutas.js'
import personas from '../paquetes/personas/rutas.js'
import agenda from '../paquetes/agenda/rutas.js'
import citas from '../paquetes/citas/rutas.js'
import reportes from '../paquetes/reportes/rutas.js'
import portalPaciente from '../paquetes/portal_paciente/rutas.js'

const RUTAS = [{ ruta: '/', codigo: 'INICIO', vista: PaginaInicio }, ...seguridad, ...personas, ...agenda, ...citas, ...reportes, ...portalPaciente]

export function iniciarRutas(raiz) {
  const pintar = () => {
    const hashFull = location.hash.replace(/^#/, '') || '/'
    const [ruta] = hashFull.split('?')
    const destino = RUTAS.find((r) => r.ruta === ruta)
    if (!destino) {
      location.hash = '#/'
      return
    }
    if (destino.ruta !== '/login' && !puedeEntrar()) {
      location.hash = '#/login'
      return
    }
    const contenido = LayoutPrincipal(raiz, ruta)
    destino.vista(contenido)
  }
  window.addEventListener('hashchange', pintar)
  pintar()
}
