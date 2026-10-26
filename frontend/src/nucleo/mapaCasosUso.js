/**
 * Los 6 paquetes del sistema y sus 17 casos de uso (mismo orden que el backend).
 * Todos los casos de uso están PENDIENTES.
 */
export const PAQUETES = [
  {
    "paquete": "seguridad",
    "titulo": "Seguridad: acceso, usuarios, roles y bitácora",
    "casos": [
      {
        "codigo": "CU01",
        "nombre": "Iniciar y cerrar sesión",
        "ruta": "/login",
        "ciclo": 1,
        "actores": [
          "Administrador",
          "Recepcionista",
          "Médico",
          "Paciente"
        ]
      },
      {
        "codigo": "CU02",
        "nombre": "Gestionar usuarios",
        "ruta": "/seguridad/usuarios",
        "ciclo": 1,
        "actores": [
          "Administrador"
        ]
      },
      {
        "codigo": "CU03",
        "nombre": "Gestionar roles y permisos",
        "ruta": "/seguridad/roles",
        "ciclo": 1,
        "actores": [
          "Administrador"
        ]
      }
    ]
  },
  {
    "paquete": "personas",
    "titulo": "Personas: pacientes, médicos y especialidades",
    "casos": [
      {
        "codigo": "CU04",
        "nombre": "Gestionar pacientes",
        "ruta": "/personas/pacientes",
        "ciclo": 1,
        "actores": [
          "Recepcionista",
          "Administrador"
        ]
      },
      {
        "codigo": "CU05",
        "nombre": "Gestionar médicos",
        "ruta": "/personas/medicos",
        "ciclo": 1,
        "actores": [
          "Administrador"
        ]
      },
      {
        "codigo": "CU06",
        "nombre": "Gestionar especialidades",
        "ruta": "/personas/especialidades",
        "ciclo": 1,
        "actores": [
          "Administrador"
        ]
      }
    ]
  },
  {
    "paquete": "agenda",
    "titulo": "Agenda: horarios de atención y disponibilidad",
    "casos": [
      {
        "codigo": "CU07",
        "nombre": "Gestionar horarios de atención",
        "ruta": "/agenda/horarios",
        "ciclo": 1,
        "actores": [
          "Administrador"
        ]
      },
      {
        "codigo": "CU08",
        "nombre": "Consultar disponibilidad de citas",
        "ruta": "/agenda/disponibilidad",
        "ciclo": 1,
        "actores": [
          "Recepcionista",
          "Paciente"
        ]
      }
    ]
  },
  {
    "paquete": "citas",
    "titulo": "Citas: programación, estados, agenda e historial",
    "casos": [
      {
        "codigo": "CU09",
        "nombre": "Programar cita médica",
        "ruta": "/citas/programar",
        "ciclo": 1,
        "actores": [
          "Recepcionista"
        ]
      },
      {
        "codigo": "CU10",
        "nombre": "Reprogramar cita",
        "ruta": "/citas/reprogramar",
        "ciclo": 2,
        "actores": [
          "Recepcionista"
        ]
      },
      {
        "codigo": "CU11",
        "nombre": "Cancelar cita",
        "ruta": "/citas/cancelar",
        "ciclo": 2,
        "actores": [
          "Recepcionista",
          "Paciente"
        ]
      },
      {
        "codigo": "CU12",
        "nombre": "Registrar asistencia de la cita",
        "ruta": "/citas/asistencia",
        "ciclo": 2,
        "actores": [
          "Recepcionista",
          "Médico"
        ]
      },
      {
        "codigo": "CU13",
        "nombre": "Consultar agenda médica",
        "ruta": "/citas/agenda",
        "ciclo": 2,
        "actores": [
          "Médico",
          "Recepcionista",
          "Administrador"
        ]
      },
      {
        "codigo": "CU14",
        "nombre": "Consultar historial de citas del paciente",
        "ruta": "/citas/historial",
        "ciclo": 2,
        "actores": [
          "Recepcionista",
          "Médico",
          "Paciente"
        ]
      }
    ]
  },
  {
    "paquete": "reportes",
    "titulo": "Reportes administrativos",
    "casos": [
      {
        "codigo": "CU15",
        "nombre": "Generar reportes",
        "ruta": "/reportes",
        "ciclo": 2,
        "actores": [
          "Administrador",
          "Recepcionista"
        ]
      }
    ]
  },
  {
    "paquete": "portal_paciente",
    "titulo": "Portal del paciente: perfil y reservas",
    "casos": [
      {
        "codigo": "CU16",
        "nombre": "Gestionar perfil del paciente",
        "ruta": "/portal/perfil",
        "ciclo": 2,
        "actores": [
          "Paciente"
        ]
      },
      {
        "codigo": "CU17",
        "nombre": "Reservar y dar seguimiento a mis citas",
        "ruta": "/portal/mis-citas",
        "ciclo": 2,
        "actores": [
          "Paciente"
        ]
      }
    ]
  }
]

// ---- CU01: inicio ----
/** Permiso que exige cada caso de uso en el backend (encabezado de casos_uso/cuXX_*.php). CU01 es público. */
export const PERMISO_POR_CU = {
  CU02: 'usuario.gestionar', CU03: 'rol.gestionar',
  CU04: 'paciente.gestionar', CU05: 'medico.gestionar', CU06: 'especialidad.gestionar',
  CU07: 'horario.gestionar', CU08: 'agenda.consultar',
  CU09: 'cita.programar', CU10: 'cita.programar', CU11: 'cita.programar', CU12: 'cita.asistencia',
  CU13: 'agenda.consultar', CU14: 'agenda.consultar',
  CU15: 'reporte.generar',
  CU16: 'perfil.paciente', CU17: 'perfil.paciente',
}
// ---- CU01: fin ----
