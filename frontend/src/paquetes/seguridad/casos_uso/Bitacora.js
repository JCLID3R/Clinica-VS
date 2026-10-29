// file Bitacora.js
import { api } from '../../../nucleo/api.js';

export default async function Bitacora() {
  // Buscamos el contenedor de la vista
  const contenedor = document.querySelector('main') || document.querySelector('#app') || document.querySelector('#contenedor-principal');
  
  if (!contenedor) {
    console.error("No se encontró el contenedor para la bitácora");
    return;
  }

  // Pintamos la estructura base
  contenedor.innerHTML = `
    <div style="padding: 20px;">
      <h2>Bitácora del Sistema</h2>
      <p>Registro de auditoría de las acciones realizadas.</p>
      <table style="width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left;">
        <thead>
          <tr style="background-color: #134E4A; color: white;">
            <th style="padding: 10px;">Fecha</th>
            <th style="padding: 10px;">Usuario</th>
            <th style="padding: 10px;">Acción</th>
            <th style="padding: 10px;">Detalle</th>
            <th style="padding: 10px;">IP</th>
          </tr>
        </thead>
        <tbody id="tabla-bitacora">
          <tr><td colspan="5" style="padding: 10px; text-align: center;">Cargando datos...</td></tr>
        </tbody>
      </table>
    </div>
  `;

  try {
    // Hacemos la petición al backend
    const respuesta = await api.get('/seguridad/bitacora');
    const logs = respuesta.bitacora || []; // Si la respuesta viene vacía, usamos un arreglo
    const tbody = document.querySelector('#tabla-bitacora');

    if (logs.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" style="padding: 10px; text-align: center;">No hay registros aún. Inicia sesión para probar.</td></tr>`;
      return;
    }

    // Pintamos las filas
    tbody.innerHTML = logs.map(log => {
      const fecha = new Date(log.fecha).toLocaleString('es-ES');
      return `
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px;">${fecha}</td>
          <td style="padding: 10px;">${log.username || 'Sistema'}</td>
          <td style="padding: 10px;"><strong>${log.accion}</strong></td>
          <td style="padding: 10px;">${log.detalle || ''}</td>
          <td style="padding: 10px;">${log.ip || ''}</td>
        </tr>
      `;
    }).join('');

  } catch (error) {
    // Si hay un error (por ejemplo, el backend falló), lo mostramos en la tabla
    document.querySelector('#tabla-bitacora').innerHTML = `
      <tr><td colspan="5" style="padding: 10px; text-align: center; color: red;">
        <strong>Error:</strong> ${error.message}
      </td></tr>
    `;
  }
}
