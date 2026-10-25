# Sistema Web de Gestión de Citas Médicas — Clínica Vida Salud

Sistema web para la gestión de citas médicas de la Clínica Vida Salud: pacientes, médicos, especialidades, horarios de atención, programación de citas sin superposiciones, control de asistencia, agenda médica, historial, reportes y portal del paciente.

Grupo #12 — Sistemas de Información I (SA) — Examen Parcial II.

## Carpetas

| Carpeta | Contenido | Guía |
|---|---|---|
| `basededato/sql/` | Base de datos PostgreSQL: 14 tablas con PK, FK, CHECK, UNIQUE y restricciones de exclusión, funciones, triggers, vistas y datos de prueba | [LEEME.md](basededato/sql/LEEME.md) |
| `backend/` | API en **PHP 8** con PDO (pdo_pgsql): **toda la lógica de negocio**, sesión y permisos | [README.md](backend/README.md) |
| `frontend/` | Aplicación web en **HTML5, CSS3 y JavaScript** (módulos ES, sin compilación) | [README.md](frontend/README.md) |
| `doc/` | Documentación PUDS-UML del proyecto (Ciclo #1) | — |
| `despliegue/` + `Dockerfile` | Publicación en la nube: **Supabase** (base de datos) + **Render** (backend y frontend) | [README.md](despliegue/README.md) |

## Arquitectura

```
Navegador (HTML/CSS/JS) ──JSON (fetch)──► API PHP (backend/public/index.php) ──PDO──► PostgreSQL
                         ◄──cookie de sesión──  lógica de negocio por paquete        datos + relaciones
                                                permisos por rol (Sesion)             triggers: validación de citas,
                                                validaciones (Validador)              transiciones de estado, historial
```

- **Frontend:** pantallas. Solo habla con el backend (`frontend/src/nucleo/api.js`).
- **Backend:** reglas de negocio, inicio de sesión (password_hash / password_verify) y permisos. Es el único que accede a la base.
- **Base de datos:** guarda los datos y protege las reglas críticas (no hay citas superpuestas, no se borran citas, transiciones de estado válidas).

## Versiones obligatorias (todo el equipo)

| Herramienta | Versión |
|---|---|
| PHP | 8.2 o superior (XAMPP 8.2) con la extensión `pdo_pgsql` habilitada |
| PostgreSQL | 16.x (pgAdmin 4) |
| Apache | 2.4 (incluido en XAMPP) |
| Navegador | Chrome, Edge o Firefox actualizados |

No se usan librerías externas: ni Composer en el backend ni npm en el frontend.

## Paquetes y casos de uso

El backend y el frontend se organizan igual: **6 paquetes**, cada uno con sus archivos compartidos y **un archivo por caso de uso** en `casos_uso/`.

**Estado:** estructura lista; los 17 casos de uso están **PENDIENTES** (cada archivo describe su flujo, permisos, tablas y reglas que debe cumplir).

| Paquete | Casos de uso |
|---|---|
| `seguridad` | CU01 Iniciar y cerrar sesión · CU02 Gestionar usuarios · CU03 Gestionar roles y permisos |
| `personas` | CU04 Gestionar pacientes · CU05 Gestionar médicos · CU06 Gestionar especialidades |
| `agenda` | CU07 Gestionar horarios de atención · CU08 Consultar disponibilidad de citas |
| `citas` | CU09 Programar cita médica · CU10 Reprogramar cita · CU11 Cancelar cita · CU12 Registrar asistencia de la cita · CU13 Consultar agenda médica · CU14 Consultar historial de citas del paciente |
| `reportes` | CU15 Generar reportes |
| `portal_paciente` | CU16 Gestionar perfil del paciente · CU17 Reservar y dar seguimiento a mis citas |

| Ciclo | Casos de uso |
|---|---|
| Ciclo #1 | CU01, CU02, CU03, CU04, CU05, CU06, CU07, CU08, CU09 |
| Ciclo #2 | CU10, CU11, CU12, CU13, CU14, CU15, CU16, CU17 |

## Estructura

```
clinica-vida-salud/
├── basededato/sql/            01_esquema … 06_datos_prueba, pruebas/, LEEME.md
├── backend/
│   ├── public/index.php       controlador frontal de la API (+ .htaccess para Apache)
│   ├── app/nucleo/            Config, BaseDatos (PDO), Enrutador, Sesion, Validador, Errores, Respuesta, Cors
│   ├── app/paquetes/<paquete>/
│   │   ├── router.php         endpoints del paquete -> casos de uso
│   │   ├── schemas.php        validación de datos de entrada
│   │   ├── models.php         tablas del paquete
│   │   ├── repository.php     consultas SQL (PDO)
│   │   ├── politicas.php      reglas de negocio y permisos comunes
│   │   └── casos_uso/cuXX_*.php   un archivo por caso de uso
│   └── tests/                 pruebas de la API
├── frontend/
│   ├── index.html
│   └── src/
│       ├── nucleo/            api.js, rutas.js, mapaCasosUso.js, layout/, auth/
│       ├── componentes/       componentes reutilizables
│       └── paquetes/<paquete>/  api.js, rutas.js, schemas.js, componentes/, casos_uso/CUXX*.js
├── doc/
├── despliegue/               apache.conf, php.ini, iniciar.sh, README.md (guía Supabase + Render)
└── Dockerfile                 imagen para la nube (PHP 8.3 + Apache + pdo_pgsql)
```

## Orden para levantar todo en local

1. **Base de datos:** sigue `basededato/sql/LEEME.md`.
2. **Backend:** sigue `backend/README.md` → <http://localhost:8000/api/v1/salud>.
3. **Frontend:** sigue `frontend/README.md` → <http://localhost:5500>.

## Publicar en la nube

Base de datos en **Supabase** y aplicación en **Render**: sigue [`despliegue/README.md`](despliegue/README.md).
En local cada uno usa su PostgreSQL; solo cambia el `.env` (en la nube, las variables de entorno de Render).

## Forma de trabajo en equipo

1. Antes de empezar: `git pull`.
2. Cada caso de uso en su propia rama: `git checkout -b cu09-programar-cita`.
3. Se modifica solo el paquete del caso de uso (backend y frontend) y, si hace falta, su script SQL.
4. Al terminar: cambiar `Estado: PENDIENTE` por `Estado: IMPLEMENTADO` en el encabezado del archivo, `git add`, `git commit -m "CU09: programar cita"`, `git push` y abrir un Pull Request.

## Integrantes

**Docente:** MSc. Ing. Angélica Garzón Cuéllar · **Materia:** Sistemas de Información I - SA

| N.º | Apellidos y nombres |
|---|---|
| 1 | Cervantes Dueñas Juan Clider — 222007907 |
| 2 | Cusi Mamani Benjamin — 222008199 |
