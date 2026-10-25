# Frontend — Clínica Vida Salud (HTML5 + CSS3 + JavaScript)

Interfaz web del sistema. Solo habla con el backend mediante `src/nucleo/api.js`.
No necesita instalar nada: son archivos HTML, CSS y JavaScript (módulos ES) que se abren desde un servidor web.

> **Estado:** estructura lista. El menú muestra los 6 paquetes y los 17 casos de uso; cada pantalla muestra "Pendiente de implementar".

## Levantar en local

El backend debe estar corriendo (ver `backend/README.md`).

**Opción A — servidor de PHP:**

```bash
cd frontend
php -S localhost:5500
```

Abrir <http://localhost:5500>. (También sirve la extensión *Live Server* de VS Code en el puerto 5500.)

**Opción B — XAMPP:** abrir <http://localhost/clinica-vida-salud/frontend/>. La dirección de la API se detecta sola
(ver `src/nucleo/config.js`), no hay que cambiar nada.

> Los módulos JavaScript no funcionan abriendo `index.html` con doble clic (`file://`); siempre usar un servidor.

## Estructura

```
frontend/
├── index.html
├── public/favicon.svg
└── src/
    ├── main.js                    punto de entrada
    ├── estilos/app.css
    ├── componentes/               comunes.js (el(), aviso()), CasoUsoPendiente.js
    ├── nucleo/
    │   ├── config.js              API_URL
    │   ├── api.js                 get / post / put / patch / del contra la API
    │   ├── rutas.js               enrutador con #/ruta
    │   ├── mapaCasosUso.js        paquetes y casos de uso del menú
    │   ├── PaginaInicio.js
    │   ├── layout/LayoutPrincipal.js
    │   └── auth/                  sesion.js, rutaProtegida.js
    └── paquetes/<paquete>/
        ├── api.js                 llamadas a los endpoints del paquete
        ├── rutas.js               ruta -> pantalla del caso de uso
        ├── schemas.js             validaciones de formularios
        ├── componentes/           componentes propios del paquete
        └── casos_uso/CUXX*.js     una pantalla por caso de uso
```

## Cómo implementar una pantalla

1. En `paquetes/<paquete>/api.js` agregar las funciones que llaman al backend, p. ej. `listarEspecialidades: () => api.get('/personas/especialidades')`.
2. En `casos_uso/CU06GestionarEspecialidades.js` construir el formulario/listado con `el()` y quitar `CasoUsoPendiente`.
3. Validar en `schemas.js` antes de enviar (el backend vuelve a validar).
