/**
 * Llamadas a la API del paquete seguridad.
 * Usar las funciones de ../../nucleo/api.js (get, post, put, patch, del).
 *
 * Endpoints del backend (backend/app/paquetes/seguridad/router.php):
 *   CU01  POST   /seguridad/sesion                      Iniciar sesión
 *   CU01  DELETE /seguridad/sesion                      Cerrar sesión
 *   CU01  GET    /seguridad/sesion                      Usuario de la sesión actual
 *   CU02  GET    /seguridad/usuarios                    Listar usuarios
 *   CU02  POST   /seguridad/usuarios                    Registrar usuario
 *   CU02  PUT    /seguridad/usuarios/{id}               Modificar usuario
 *   CU02  PATCH  /seguridad/usuarios/{id}/estado        Activar o desactivar usuario
 *   CU03  GET    /seguridad/roles                       Listar roles
 *   CU03  GET    /seguridad/permisos                    Listar permisos
 *   CU03  GET    /seguridad/roles/{id}/permisos         Permisos de un rol
 *   CU03  PUT    /seguridad/roles/{id}/permisos         Asignar permisos a un rol
 *
 * Estado: PENDIENTE.
 */
import { api } from '../../nucleo/api.js'

export default {}
