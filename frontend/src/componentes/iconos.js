/**
 * Iconos SVG del sistema (línea, 1.75px stroke, 20px, currentColor)
 * Uso: icono.logo(), icono.panelPrincipal(), etc.
 */

function svg(contenido, size = 20) {
  const el = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  el.setAttribute('width', size)
  el.setAttribute('height', size)
  el.setAttribute('viewBox', '0 0 24 24')
  el.setAttribute('fill', 'none')
  el.setAttribute('stroke', 'currentColor')
  el.setAttribute('stroke-width', '1.75')
  el.setAttribute('stroke-linecap', 'round')
  el.setAttribute('stroke-linejoin', 'round')
  el.innerHTML = contenido
  return el
}

function svgLogo(contenido, size = 24) {
  const el = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  el.setAttribute('width', size)
  el.setAttribute('height', size)
  el.setAttribute('viewBox', '0 0 24 24')
  el.setAttribute('fill', 'none')
  el.setAttribute('stroke', 'currentColor')
  el.setAttribute('stroke-width', '2')
  el.setAttribute('stroke-linecap', 'round')
  el.setAttribute('stroke-linejoin', 'round')
  el.innerHTML = contenido
  return el
}

// ---- REDISENO: inicio ----
export const icono = {
  // Logo: cruz médica
  logo: (size = 24) => svgLogo('<path d="M9.5 4h5v5.5H20v5h-5.5V20h-5v-5.5H4v-5h5.5z"/>', size),

  // Menú
  panelPrincipal: (size = 20) => svg('<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>', size),
  programarCita: (size = 20) => svg('<rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M16 2.5v4M8 2.5v4M3 10h18M12 13.5v5M9.5 16h5"/>', size),
  agendaMedica: (size = 20) => svg('<rect x="5" y="3.5" width="14" height="18" rx="2.5"/><path d="M9 3.5V2.5h6v1M9 9h6M9 13h6M9 17h3"/>', size),
  pacientes: (size = 20) => svg('<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.3-5.5 6.5-5.5s5.9 1.9 6.5 5.5M16 4.8a3.4 3.4 0 0 1 0 6.4M18.5 14.8c1.7.8 2.8 2.5 3 5.2"/>', size),
  medicos: (size = 20) => svg('<path d="M6 3v6a5 5 0 0 0 10 0V3M6 3H4.5M16 3h1.5M11 14v2.5a4.5 4.5 0 0 0 9 0V14"/><circle cx="20" cy="12" r="2"/>', size),
  especialidades: (size = 20) => svg('<path d="M12 20.5s-8-4.6-8-10.5a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.9-8 10.5-8 10.5z"/><path d="M8.5 12h2l1-2 1.5 4 1-2h1.5"/>', size),
  horarios: (size = 20) => svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>', size),
  usuarios: (size = 20) => svg('<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4 3.9-6 8-6s7.2 2 8 6"/>', size),
  rolesPermisos: (size = 20) => svg('<path d="M12 2.5 4.5 5.5v6c0 4.6 3.2 8.3 7.5 10 4.3-1.7 7.5-5.4 7.5-10v-6z"/><path d="m9 12 2.2 2.2L15.5 10"/>', size),
  bitacora: (size = 20) => svg('<path d="M14 2.5H6.5A2 2 0 0 0 4.5 4.5v15a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V8z"/><path d="M14 2.5V8h5.5M8.5 13h7M8.5 17h5"/>', size),

  // Cerrar
  cerrar: (size = 20) => svg('<path d="M5 5l14 14M19 5L5 19"/>', size),

  // Menú usuario
  miCuenta: (size = 20) => svg('<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4 3.9-6 8-6s7.2 2 8 6"/>', size),
  cerrarSesion: (size = 20) => svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 3l.001.001M20 9v6a2 2 0 0 1-2 2h-2m0-6l3-3m0 6l-3-3"/>', size),

  // Cabecera
  hamburguesa: (size = 20) => {
    const el = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
    el.setAttribute('width', size)
    el.setAttribute('height', size)
    el.setAttribute('viewBox', '0 0 24 24')
    el.setAttribute('fill', 'none')
    el.setAttribute('stroke', 'currentColor')
    el.setAttribute('stroke-width', '2')
    el.setAttribute('stroke-linecap', 'round')
    el.innerHTML = '<path d="M4 6h16M4 12h16M4 18h16"/>'
    return el
  },
  calendario: (size = 16) => svg('<rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M16 2.5v4M8 2.5v4M3 10h18"/>', size),
  flechaAbajo: (size = 18) => svg('<path d="m6 9 6 6 6-6"/>', size),

  // Notificaciones y estados
  campana: (size = 20) => svg('<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/>', size),
  asistencia: (size = 20) => svg('<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>', size),
  cumplido: (size = 16) => svg('<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>', size),
  noCumplido: (size = 16) => svg('<circle cx="12" cy="12" r="8"/>', size),
}
// ---- REDISENO: fin ----
