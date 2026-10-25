/**
 * Utilidades para construir la interfaz sin librerías.
 *   el('button', { class: 'btn', onclick: fn }, 'Guardar')
 * El texto siempre se inserta como texto (no como HTML) para evitar inyección de código (XSS).
 */
export function el(etiqueta, atributos = {}, hijos = []) {
  const nodo = document.createElement(etiqueta)
  for (const [clave, valor] of Object.entries(atributos)) {
    if (clave.startsWith('on') && typeof valor === 'function') nodo.addEventListener(clave.slice(2), valor)
    else if (valor !== false && valor !== null && valor !== undefined) nodo.setAttribute(clave, valor)
  }
  for (const hijo of [].concat(hijos)) nodo.append(hijo instanceof Node ? hijo : document.createTextNode(String(hijo)))
  return nodo
}

/** Muestra un mensaje temporal (éxito o error) en la esquina de la pantalla. */
export function aviso(mensaje, tipo = 'ok') {
  const nodo = el('div', { class: `aviso ${tipo}` }, mensaje)
  document.body.append(nodo)
  setTimeout(() => nodo.remove(), 3500)
}
