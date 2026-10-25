/** Rutas del paquete seguridad: cada ruta muestra la pantalla de su caso de uso. */
import CU01IniciarSesion from './casos_uso/CU01IniciarSesion.js'
import CU02GestionarUsuarios from './casos_uso/CU02GestionarUsuarios.js'
import CU03GestionarRolesPermisos from './casos_uso/CU03GestionarRolesPermisos.js'

export default [
  { ruta: '/login', codigo: 'CU01', vista: CU01IniciarSesion },
  { ruta: '/seguridad/usuarios', codigo: 'CU02', vista: CU02GestionarUsuarios },
  { ruta: '/seguridad/roles', codigo: 'CU03', vista: CU03GestionarRolesPermisos },
]
