# Base de datos — Clínica Vida Salud (PostgreSQL 16)

La base guarda **los datos y sus relaciones** y protege las reglas críticas. **La lógica de negocio está en el backend** (PHP).

## Qué hay en la base

| Archivo | Contenido |
|---|---|
| `01_esquema.sql` | Extensión `btree_gist`, 14 tablas con PK, FK, CHECK, UNIQUE, restricciones de exclusión e índices |
| `02_funciones.sql` | `fn_validar_cita`, `fn_transicion_estado`, `fn_historial_cita`, `fn_impedir_borrado`, `fn_horarios_disponibles` |
| `03_triggers.sql` | Triggers que enganchan las funciones a `cita`, `paciente` y `medico` |
| `04_vistas.sql` | `v_agenda_medica` (agenda e historial) y `v_resumen_estados` (reportes) |
| `05_datos_iniciales.sql` | Roles, permisos, usuario `admin` y especialidades |
| `06_datos_prueba.sql` | Usuarios, médicos, horarios, pacientes y citas de prueba (ficticios) |
| `07_datos_demo.sql` | Datos de demostración adicionales (Ciclo 1) — *opcional* |
| `pruebas/10_pruebas.sql` | Pruebas de las reglas de la base (CI duplicado, citas superpuestas, fuera de horario…) |
| `99_reiniciar.sql` | Borra todo el esquema (solo en desarrollo) |

**Reglas protegidas en la base:**

| Regla | Mecanismo |
|---|---|
| Un médico no tiene citas superpuestas (RN07); las canceladas no ocupan horario (RN09) | `EXCLUDE USING gist` en `cita` |
| Cita dentro del horario de atención, no en fechas pasadas, médico y paciente activos (RN04–RN06, RN08) | `fn_validar_cita` |
| Solo transiciones de estado válidas | `fn_transicion_estado` |
| Historial de cada cambio de estado y reprogramación | `fn_historial_cita` |
| No se eliminan citas, pacientes ni médicos (RN10, RN11) | `fn_impedir_borrado` |
| CI único (BIGINT) y especialidad con nombre único normalizado | `UNIQUE` |

## Crear la base (una vez)

Con **pgAdmin**: crear la base `clinica_vida_salud`, abrir el *Query Tool* y ejecutar los archivos **en orden** del 01 al 06.

Con **psql** (desde la carpeta `basededato/sql`):

```bash
psql -U postgres -c "CREATE DATABASE clinica_vida_salud"
psql -U postgres -d clinica_vida_salud -f 01_esquema.sql
psql -U postgres -d clinica_vida_salud -f 02_funciones.sql
psql -U postgres -d clinica_vida_salud -f 03_triggers.sql
psql -U postgres -d clinica_vida_salud -f 04_vistas.sql
psql -U postgres -d clinica_vida_salud -f 05_datos_iniciales.sql
psql -U postgres -d clinica_vida_salud -f 06_datos_prueba.sql
```

Probar las reglas: `psql -U postgres -d clinica_vida_salud -f pruebas/10_pruebas.sql` (los `ERROR` que aparecen son los rechazos esperados).

## Usuarios de prueba

Contraseña de todos: `Clinica2026*`

| Usuario | Rol |
|---|---|
| admin | Administrador |
| recepcion1 | Recepcionista |
| jperez, arojas | Médico |
| mlopez | Paciente |

## Reiniciar la base (desarrollo)

Ejecutar `99_reiniciar.sql` y luego otra vez los archivos 01 a 06.
