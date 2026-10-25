/** Marcador reutilizable para los casos de uso que aún no se implementan. */
import { el } from './comunes.js'

export default function CasoUsoPendiente(contenedor, { codigo, nombre, paquete }) {
  contenedor.append(
    el('div', { class: 'pendiente' }, [
      el('p', { class: 'ruta' }, `paquetes/${paquete}`),
      el('h1', {}, `${codigo} - ${nombre}`),
      el('p', {}, 'Pendiente de implementar.'),
    ]),
  )
}
