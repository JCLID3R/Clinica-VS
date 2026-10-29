// file Bitacora.js
import { api } from '../../../nucleo/api.js';

export default async function Bitacora() {
  const contenedor = document.querySelector('#contenedor-principal');
  if (!contenedor) return;

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
    // Llamamos al endpoint que creamos en el router.php
    const respuesta = await api.get('/seguridad/bitacora');
    const logs = respuesta.bitacora;
    const tbody = document.querySelector('#tabla-bitacora');

    if (logs.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" style="padding: 10px; text-align: center;">No hay registros aún.</td></tr>`;
      return;
    }

    // Formateamos las filas
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
    document.querySelector('#tabla-bitacora').innerHTML = `
      <tr><td colspan="5" style="padding: 10px; text-align: center; color: red;">
        Error al cargar la bitácora: ${error.message}
      </td></tr>
    `;
  }
}
