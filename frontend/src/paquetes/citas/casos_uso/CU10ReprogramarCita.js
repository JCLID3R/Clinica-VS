/**
 * CU10ReprogramarCita.js
 * CU10 - Reprogramar cita  (paquete citas, Ciclo 2)
 * Actores: Recepcionista
 *
 * Pantalla del caso de uso. Llama a la API mediante ../api.js y valida con ../schemas.js.
 * Estado: PENDIENTE.
 */
import { api } from '../../../nucleo/api.js';

 async function CU10ReprogramarCita() {
  const contenedor = document.querySelector('#contenedor-principal');
  if (!contenedor) return;

  // HTML + CSS embebido (patrón del proyecto: sin archivos .html/.css sueltos)
  contenedor.innerHTML = `
    <style>
      .cu10 { max-width: 560px; margin: 1.5rem auto; font-family: system-ui, sans-serif; }
      .cu10 h2 { margin-bottom: 1rem; }
      .cu10 .campo { display: grid; gap: .35rem; margin-bottom: .9rem; }
      .cu10 label { font-weight: 600; font-size: .9rem; }
      .cu10 input { padding: .5rem .6rem; border: 1px solid #cbd5e1; border-radius: 6px; }
      .cu10 button { padding: .6rem 1rem; border: 0; border-radius: 6px; cursor: pointer; font-weight: 600; }
      .cu10 .btn-buscar { background: #0ea5e9; color: #fff; }
      .cu10 .btn-guardar { background: #16a34a; color: #fff; }
      .cu10 .msg { margin-top: .8rem; padding: .6rem; border-radius: 6px; font-size: .9rem; }
      .cu10 .msg.ok { background: #dcfce7; color: #166534; }
      .cu10 .msg.error { background: #fee2e2; color: #991b1b; }
      .cu10 .oculto { display: none; }
    </style>

    <section class="cu10">
      <h2>Reprogramar cita</h2>

      <!-- Paso 1: buscar cita -->
      <div id="cu10-buscar">
        <div class="campo">
          <label for="cu10-id-cita">ID de la cita</label>
          <input type="number" id="cu10-id-cita" placeholder="Ej. 15" min="1">
        </div>
        <button class="btn-buscar" id="cu10-btn-buscar">Buscar cita</button>
      </div>

      <!-- Paso 2: datos y nuevo horario (oculto hasta buscar) -->
      <div id="cu10-form" class="oculto">
        <p><strong>Cita:</strong> <span id="cu10-info"></span></p>

        <div class="campo">
          <label for="cu10-id-horario">ID del nuevo horario disponible</label>
          <input type="number" id="cu10-id-horario" placeholder="Ej. 42" min="1">
        </div>

        <button class="btn-guardar" id="cu10-btn-guardar">Reprogramar</button>
      </div>

      <p id="cu10-msg" class="msg oculto"></p>
    </section>
  `;

  // Referencias
  const inputIdCita   = document.querySelector('#cu10-id-cita');
  const btnBuscar     = document.querySelector('#cu10-btn-buscar');
  const divForm       = document.querySelector('#cu10-form');
  const divBuscar     = document.querySelector('#cu10-buscar');
  const spanInfo      = document.querySelector('#cu10-info');
  const inputIdHorario= document.querySelector('#cu10-id-horario');
  const btnGuardar    = document.querySelector('#cu10-btn-guardar');
  const msg           = document.querySelector('#cu10-msg');

  let citaActual = null;

  function mostrarMsg(texto, tipo) {
    msg.textContent = texto;
    msg.className = 'msg ' + tipo;
  }

  // Buscar cita
  btnBuscar.addEventListener('click', async () => {
    const id = inputIdCita.value.trim();
    if (!id) return mostrarMsg('Ingrese un ID de cita', 'error');

    try {
      // Ajusta la ruta según router.php: GET /citas/{id}
      citaActual = await api.get(`/citas/${id}`);
      spanInfo.textContent = `#${citaActual.id} — ${citaActual.paciente ?? ''} | ${citaActual.fecha} ${citaActual.hora} | Estado: ${citaActual.estado}`;
      divBuscar.classList.add('oculto');
      divForm.classList.remove('oculto');
      mostrarMsg('Cita encontrada. Seleccione el nuevo horario.', 'ok');
    } catch (e) {
      mostrarMsg(e.message || 'No se encontró la cita', 'error');
    }
  });


  // Guardar reprogramación
  btnGuardar.addEventListener('click', async () => {
    const idHorario = inputIdHorario.value.trim();
    if (!idHorario) return mostrarMsg('Ingrese el ID del nuevo horario', 'error');

    try {
      const resp = await api.patch(`/citas/${citaActual.id}/reprogramar`, {
        id_horario: Number(idHorario),
      });
      mostrarMsg(resp.mensaje || 'Cita reprogramada correctamente', 'ok');
      // Refrescar info
      spanInfo.textContent = `#${resp.cita.id} — ${resp.cita.paciente ?? ''} | ${resp.cita.fecha} ${resp.cita.hora} | Estado: ${resp.cita.estado}`;
    } catch (e) {
      mostrarMsg(e.message || 'Error al reprogramar la cita', 'error');
    }
  });
}
export default CU10ReprogramarCita;
