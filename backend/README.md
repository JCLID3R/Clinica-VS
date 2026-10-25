# Backend — API de la Clínica Vida Salud (PHP 8 + PostgreSQL)

API en PHP que usa el frontend. **Aquí está la lógica de negocio del sistema**: inicio de sesión, permisos por rol, validaciones y altas, bajas y modificaciones. La base de datos (`basededato/sql/`) guarda los datos y protege las reglas críticas con restricciones y triggers.

> **Estado:** estructura lista. Los 17 casos de uso están **PENDIENTES**: sus endpoints existen y responden `501 Pendiente de implementar` (o `401` si exigen sesión). El encabezado de cada archivo de `casos_uso/` explica el flujo y las reglas que debe cumplir.

## 1. Requisitos

| Herramienta | Versión | Para qué |
|---|---|---|
| **PHP** | 8.2 o superior (XAMPP 8.2) | Lenguaje del backend |
| Extensión `pdo_pgsql` | incluida en PHP | Conexión con PostgreSQL |
| **PostgreSQL** | 16.x | Base de datos |
| Git | reciente | Control de versiones |

### Habilitar `pdo_pgsql` en XAMPP (Windows)

1. Abrir `C:\xampp\php\php.ini`.
2. Quitar el `;` de estas líneas: `extension=pdo_pgsql` y `extension=pgsql`.
3. Reiniciar Apache desde el panel de XAMPP.
4. Verificar: `php -m` debe listar `pdo_pgsql`.

## 2. Configuración

Dentro de `backend/`:

```bash
copy .env.example .env      # Windows (en Linux/macOS: cp .env.example .env)
```

Editar `.env` con la clave de tu PostgreSQL (`DB_CLAVE`). El archivo `.env` **no se sube** a GitHub.

## 3. Levantar la API

**Opción A — servidor de PHP (recomendado para desarrollar):**

```bash
cd backend
php -S localhost:8000 -t public public/index.php
```

Probar: <http://localhost:8000/api/v1/salud> y <http://localhost:8000/api/v1/salud/bd>.

**Opción B — XAMPP (Apache):** copiar el proyecto a `C:\xampp\htdocs\clinica-vida-salud` y abrir
<http://localhost/clinica-vida-salud/backend/public/api/v1/salud> (el `.htaccess` redirige todo a `index.php`).

## 4. Endpoints

`GET /api/v1/rutas` lista todos los endpoints registrados. Los principales:

| Paquete | Prefijo | Casos de uso |
|---|---|---|
| seguridad | `/api/v1/seguridad/...` | CU01, CU02, CU03 |
| personas | `/api/v1/personas/...` | CU04, CU05, CU06 |
| agenda | `/api/v1/agenda/...` | CU07, CU08 |
| citas | `/api/v1/citas/...` | CU09 – CU14 |
| reportes | `/api/v1/reportes/...` | CU15 |
| portal_paciente | `/api/v1/portal/...` | CU16, CU17 |

Formato de error: `{"error": {"estado": 400, "mensaje": "Datos inválidos.", "detalle": {"ci": "Campo obligatorio."}}}`

## 5. Estructura de un paquete

```
app/paquetes/citas/
├── router.php          rutas HTTP del paquete -> llama a los casos de uso
├── schemas.php         validación de los datos de entrada (App\Nucleo\Validador)
├── models.php          tablas que maneja el paquete
├── repository.php      consultas SQL parametrizadas (App\Nucleo\BaseDatos)
├── politicas.php       reglas de negocio y permisos comunes del paquete
└── casos_uso/
    ├── cu09_programar_cita.php
    └── ...
```

Flujo de una petición: `public/index.php` → `Enrutador` → `router.php` del paquete → `casos_uso/cuXX_*.php` → `politicas.php` / `repository.php` → PostgreSQL.

### Cómo implementar un caso de uso (ejemplo CU06)

1. En `repository.php` escribir las consultas (`BaseDatos::consultar`, `fila`, `ejecutar`, `transaccion`).
2. En `schemas.php` validar el cuerpo (`new Validador($datos)`).
3. En `casos_uso/cu06_gestionar_especialidades.php` reemplazar `throw new NoImplementado('CU06')` por la lógica y devolver un arreglo (se convierte en JSON).
4. Cambiar `Estado: PENDIENTE` por `Estado: IMPLEMENTADO` en el encabezado.
5. Agregar sus pruebas en `tests/personas/test_cu06.php`.

## 6. Núcleo (`app/nucleo/`)

| Archivo | Responsabilidad |
|---|---|
| `Config.php` | Lee el `.env` |
| `BaseDatos.php` | Conexión PDO, consultas preparadas y transacciones |
| `Enrutador.php` | Rutas `MÉTODO /ruta/{id}` y datos de la petición (`Peticion`) |
| `Sesion.php` | Sesión PHP, usuario actual y `Sesion::requerir('permiso')` |
| `Validador.php` | Validaciones del servidor |
| `Errores.php` | `ErrorApi`, `NoImplementado` y traducción de errores de PostgreSQL |
| `Respuesta.php` | Respuestas JSON |
| `Cors.php` | Permite al frontend llamar a la API desde otro puerto |

## 7. Pruebas

Con la API levantada en el puerto 8000:

```bash
php tests/ejecutar.php
```
