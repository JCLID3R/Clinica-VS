/**
 * Configuración del frontend.
 * API_URL: dirección del backend. Se detecta sola según dónde se abra el frontend:
 *   - Live Server / php -S en el puerto 5500  -> http://localhost:8000/api/v1
 *   - XAMPP  (.../clinica-vida-salud/frontend/) -> .../clinica-vida-salud/backend/public/api/v1
 *   - Nube (Render, Docker)                    -> /api/v1  (mismo servidor)
 * Para forzar otra dirección: definir window.API_URL antes de cargar main.js.
 */
function detectarApi() {
  const { port, pathname } = window.location
  if (port === '5500') return 'http://localhost:8000/api/v1'
  const i = pathname.indexOf('/frontend/')
  if (i !== -1) return pathname.slice(0, i) + '/backend/public/api/v1'
  return '/api/v1'
}

export const API_URL = window.API_URL || detectarApi()
export const NOMBRE_SISTEMA = 'Clínica Vida Salud'
