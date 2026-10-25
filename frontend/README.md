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

## Diseño responsivo (obligatorio en todas las pantallas)

El layout ya es responsivo: el botón ☰ de la esquina superior izquierda oculta o muestra el menú lateral.
En computadora el menú se oculta hacia la izquierda y se recuerda la preferencia. En tablet y celular
(hasta 900 px) el menú empieza oculto, se abre encima del contenido y se cierra con ✕, tocando fuera,
con Esc o al elegir una opción.

Para que cada caso de uso también se vea bien en celular, usar estas clases de `src/estilos/app.css`:

| Clase | Uso |
|---|---|
| `encabezado-pagina` | Título de la pantalla (`h1`) y sus botones en `acciones`; en celular los botones ocupan todo el ancho |
| `btn`, `btn secundario`, `btn peligro` | Botones |
| `acciones` | Grupo de botones que se acomoda en varias líneas |
| `tabla-contenedor` + `tabla` | Tablas: en pantallas chicas se desplazan hacia el costado sin romper la página |
| `formulario` + `campo` | Formularios en columnas que pasan a una sola columna en celular; `completo` para un campo de ancho total |
| `tarjetas` / `tarjeta` | Rejilla de tarjetas |

Probar cada pantalla en el navegador con F12 → modo dispositivo (Ctrl+Shift+M) a 375 px, 768 px y 1280 px.
