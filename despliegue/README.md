# Despliegue en la nube — Supabase + Render

| Pieza | Local (desarrollo) | Nube (producción) |
|---|---|---|
| Base de datos | PostgreSQL de tu PC | **Supabase** |
| Backend + frontend | XAMPP / `php -S` | **Render** (Docker, plan gratis) |
| Configuración | `backend/.env` | Variables de entorno en Render |

En la nube un solo servidor Apache sirve todo (ver `Dockerfile` y `despliegue/apache.conf`):

- `https://<tu-app>.onrender.com/` → frontend
- `https://<tu-app>.onrender.com/api/v1/...` → backend PHP

Como todo está en el mismo dominio no hace falta configurar CORS. El frontend detecta solo la dirección de la API (`frontend/src/nucleo/config.js`).

---

## Paso 1 — Base de datos en Supabase

1. Crear la cuenta en <https://supabase.com> con **Continue with GitHub**.
2. **New project**:
   - *Name*: `clinica-vida-salud`
   - *Database password*: generar una y **guardarla** (se usa en el paso 3).
   - *Region*: **South America (São Paulo)**, la más cercana a Bolivia.
3. **Database → Extensions**: buscar **btree_gist** y activarla.
4. **SQL Editor → New query**: pegar y ejecutar **en orden** (uno por uno):
   `01_esquema.sql` → `02_funciones.sql` → `03_triggers.sql` → `04_vistas.sql` → `05_datos_iniciales.sql`
   (y `06_datos_prueba.sql` solo si quieren datos de ejemplo).
   - ⚠️ **No ejecutar `99_reiniciar.sql` en Supabase**: borra el esquema `public` y rompe los permisos internos de Supabase.
   - `pruebas/10_pruebas.sql` usa comandos de `psql` (`\echo`), así que no corre en el SQL Editor. Las pruebas se hacen en local.
5. **Seguridad**: Supabase publica las tablas de `public` en su API REST, pero este sistema no la usa. Hay dos formas de cerrarla:
   - **Settings → Data API** → desactivar la Data API, **o**
   - en el SQL Editor ejecutar, para cada tabla: `ALTER TABLE <tabla> ENABLE ROW LEVEL SECURITY;`
     (sin políticas). El backend entra como `postgres` y no le afecta.
6. **Cambiar la contraseña del usuario `admin`** (la inicial `Clinica2026*` está publicada en el repo).
7. **Datos de conexión**: botón **Connect** (arriba) → pestaña *Connection string* → **Session pooler**. Anotar:

   | Variable | Valor (ejemplo) |
   |---|---|
   | `DB_HOST` | `aws-0-sa-east-1.pooler.supabase.com` |
   | `DB_PUERTO` | `5432` |
   | `DB_NOMBRE` | `postgres` |
   | `DB_USUARIO` | `postgres.abcdefghijklmnop` (incluye el id del proyecto) |
   | `DB_CLAVE` | la contraseña del punto 2 |
   | `DB_SSLMODE` | `require` |

   > No usar *Direct connection* (en el plan gratis solo funciona por IPv6) ni el *Transaction pooler*
   > (puerto 6543, falla con los *prepared statements* de PDO).

**Probar desde tu PC (opcional):** copia esos valores en tu `backend/.env` y abre `/api/v1/salud/bd`. Si responde `"estado":"ok"` la conexión funciona. Después vuelve a dejar tu `.env` apuntando a tu PostgreSQL local.

---

## Paso 2 — Cuenta en Render

1. Entrar a <https://render.com> → **Get Started** → **GitHub**.
2. Autorizar a Render y, cuando pregunte qué repositorios puede ver, elegir **Only select repositories → Clinica-VS**.
3. No pide tarjeta para el plan gratis.

## Paso 3 — Crear el servicio web

1. En el panel: **+ New → Web Service** → elegir el repo **Clinica-VS**.
2. Configuración:

   | Campo | Valor |
   |---|---|
   | Name | `clinica-vida-salud` (será `clinica-vida-salud.onrender.com`) |
   | Language | **Docker** (lo detecta por el `Dockerfile`) |
   | Branch | `main` |
   | Region | **Virginia (US East)** u **Ohio** |
   | Instance Type | **Free** |

3. **Environment Variables** → *Add from .env* o una por una:

   ```
   APP_ENTORNO=produccion
   DB_HOST=aws-0-sa-east-1.pooler.supabase.com
   DB_PUERTO=5432
   DB_NOMBRE=postgres
   DB_USUARIO=postgres.abcdefghijklmnop
   DB_CLAVE=********
   DB_SSLMODE=require
   ```

4. **Advanced → Health Check Path**: `/api/v1/salud`
5. **Create Web Service**. El primer despliegue tarda unos 3 a 5 minutos. Se puede seguir en *Logs*.

## Paso 4 — Comprobar

- `https://<tu-app>.onrender.com/api/v1/salud` → `{"estado":"ok", ...}`
- `https://<tu-app>.onrender.com/api/v1/salud/bd` → `{"estado":"ok","base":"postgres", ...}` (conecta con Supabase)
- `https://<tu-app>.onrender.com/` → pantalla de inicio del sistema

## Cómo se actualiza

Cada `git push` a `main` hace que Render vuelva a construir y publicar la aplicación (*Auto-Deploy*).

Los cambios de base de datos van como **script nuevo numerado** en `basededato/sql/` (por ejemplo `07_...sql`):

1. Probarlo en la base local.
2. Ejecutarlo en el SQL Editor de Supabase.
3. Hacer push del código.

No modificar tablas a mano en el panel de Supabase.

## Límites del plan gratis

| Servicio | Límite | Qué hacer |
|---|---|---|
| Render | Se "duerme" tras 15 min sin visitas; la primera visita tarda ~1 min | Abrir la página un rato antes de presentar |
| Render | 750 horas/mes | Alcanza para un servicio encendido todo el mes |
| Supabase | El proyecto se **pausa** tras 7 días sin uso | Entrar al panel y pulsar *Restore* antes de presentar |

## Problemas comunes

| Síntoma | Causa probable |
|---|---|
| `/salud/bd` → 503 | Datos de conexión mal copiados, falta `DB_SSLMODE=require`, o el proyecto de Supabase está pausado |
| Error de `btree_gist` al ejecutar `01_esquema.sql` | Falta activar la extensión (paso 1.3) |
| El login no mantiene la sesión | Acceder siempre por `https://` (la cookie es segura en producción) |
| La página tarda mucho la primera vez | Render estaba dormido; esperar ~1 min |

> **Otra opción:** el mismo `Dockerfile` funciona en **Railway** (*New Project → Deploy from GitHub repo* y las mismas variables). Allí el plan gratuito es solo un crédito de prueba.
