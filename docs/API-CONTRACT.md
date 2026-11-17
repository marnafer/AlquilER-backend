# Contrato de API — AlquilER

Referencia de las rutas HTTP registradas por el backend. Los esquemas de
solicitud/respuesta, parámetros, códigos por operación y descripciones
detalladas están en [OpenAPI](../public/docs/openapi.json), también disponible
en `GET /docs/` al iniciar el servidor.

## 1. Convenciones

- Base local con PHP integrado: `http://127.0.0.1:8000`.
- Todas las rutas de recursos están bajo `/api`; `GET /api/health` informa si
  la API está respondiendo.
- Los cuerpos JSON usan `Content-Type: application/json`. La carga de imágenes
  usa `multipart/form-data`. Los campos requeridos y los formatos se detallan
  en el esquema de cada operación OpenAPI.
- Las rutas marcadas **Pública** no declaran JWT. Las marcadas **JWT** requieren
  autenticación; además pueden aplicar permisos de administrador, titularidad
  del recurso u otras reglas de negocio. La especificación OpenAPI describe
  los requisitos conocidos por operación.
- En los listados que aceptan `solo_eliminados` o `incluir_eliminados`, pedir
  registros eliminados requiere ser administrador aunque el listado normal
  sea público.
- El JWT protegido se acepta mediante la cookie `HttpOnly` `access_token` o
  `Authorization: Bearer <token>`. El login establece cookies `access_token`
  y `refresh_token`; refresh y logout operan con el refresh token.
- Para peticiones de navegador con cookies, enviar credenciales y un `Origin`
  permitido por `CORS_ALLOWED_ORIGINS` cuando corresponda.

Las respuestas se serializan como JSON. Las respuestas exitosas usan
`success: true` y, según la operación, `data` y `message`; los errores usan
`success: false` y `error`, y pueden incluir `validation_errors`. Los códigos
HTTP y las formas exactas de respuesta varían por operación: consultar OpenAPI
en vez de asumir que todas las operaciones usan el mismo código o estructura.

## 2. Rutas

**Acceso:** Pública = sin JWT declarado; JWT = requiere autenticación. La
autorización adicional se aplica en el backend según la acción y el recurso.

### Salud y autenticación

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/health` | Pública |
| `POST` | `/api/autenticador/login` | Pública |
| `POST` | `/api/autenticador/register` | Pública |
| `POST` | `/api/autenticador/logout` | Pública; usa/invalida el refresh token |
| `POST` | `/api/autenticador/refresh` | Pública; requiere refresh token |
| `GET` | `/api/autenticador/verificar-email` | Pública |
| `POST` | `/api/autenticador/recuperar` | Pública |
| `POST` | `/api/autenticador/restablecer` | Pública |
| `POST` | `/api/autenticador/confirmar-cambio` | Pública |

### Contacto y usuarios

| Método | Ruta | Acceso |
|---|---|---|
| `POST` | `/api/contacto` | Pública |
| `GET` | `/api/usuarios` | JWT; administración |
| `GET` | `/api/usuarios/{id}` | JWT |
| `GET` | `/api/usuarios/me` | JWT |
| `GET` | `/api/usuarios/{id}/perfil-publico` | JWT |
| `PUT` | `/api/usuarios/{id}` | JWT |
| `DELETE` | `/api/usuarios/{id}` | JWT |
| `POST` | `/api/usuarios/{id}/restaurar` | JWT; administración |
| `POST` | `/api/admin/usuarios` | JWT; administración |

### Catálogos

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/categorias` | Pública |
| `POST` | `/api/categorias` | JWT; administración |
| `GET` | `/api/categorias/{id}` | Pública |
| `PUT` | `/api/categorias/{id}` | JWT; administración |
| `DELETE` | `/api/categorias/{id}` | JWT; administración |
| `POST` | `/api/categorias/{id}/restaurar` | JWT; administración |
| `GET` | `/api/provincias` | Pública |
| `POST` | `/api/provincias` | JWT; administración |
| `GET` | `/api/provincias/{id}` | Pública |
| `PUT` | `/api/provincias/{id}` | JWT; administración |
| `DELETE` | `/api/provincias/{id}` | JWT; administración |
| `POST` | `/api/provincias/{id}/restaurar` | JWT; administración |
| `GET` | `/api/localidades` | Pública |
| `POST` | `/api/localidades` | JWT; administración |
| `GET` | `/api/localidades/{id}` | Pública |
| `PUT` | `/api/localidades/{id}` | JWT; administración |
| `DELETE` | `/api/localidades/{id}` | JWT; administración |
| `POST` | `/api/localidades/{id}/restaurar` | JWT; administración |
| `GET` | `/api/roles` | JWT; administración |
| `POST` | `/api/roles` | JWT; administración |
| `GET` | `/api/roles/{id}` | JWT; administración |
| `PUT` | `/api/roles/{id}` | JWT; administración |
| `DELETE` | `/api/roles/{id}` | JWT; administración |
| `POST` | `/api/roles/{id}/restaurar` | JWT; administración |
| `GET` | `/api/servicios` | Pública |
| `POST` | `/api/servicios` | JWT; administración |
| `GET` | `/api/servicios/{id}` | Pública |
| `PUT` | `/api/servicios/{id}` | JWT; administración |
| `DELETE` | `/api/servicios/{id}` | JWT; administración |
| `POST` | `/api/servicios/{id}/restaurar` | JWT; administración |

### Propiedades, imágenes y servicios asociados

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/propiedades` | Pública |
| `POST` | `/api/propiedades` | JWT |
| `GET` | `/api/propiedades/destacadas` | Pública |
| `GET` | `/api/propiedades/mis-propiedades` | JWT |
| `GET` | `/api/propiedades/{id}` | Pública |
| `PUT` | `/api/propiedades/{id}` | JWT |
| `DELETE` | `/api/propiedades/{id}` | JWT |
| `POST` | `/api/propiedades/{id}/restaurar` | JWT |
| `GET` | `/api/admin/propiedades` | JWT; administración |
| `GET` | `/api/propiedad-imagenes` | Pública |
| `POST` | `/api/propiedad-imagenes` | JWT; carga `multipart/form-data` |
| `GET` | `/api/propiedad-imagenes/{id}` | Pública |
| `PUT` | `/api/propiedad-imagenes/{id}/principal` | JWT |
| `DELETE` | `/api/propiedad-imagenes/{id}` | JWT |
| `GET` | `/api/propiedades/{propiedadId}/servicios` | Pública |
| `POST` | `/api/propiedades/{propiedadId}/servicios` | JWT |
| `POST` | `/api/propiedades/{propiedadId}/servicios/multiple` | JWT |
| `PUT` | `/api/propiedades/{propiedadId}/servicios` | JWT |
| `DELETE` | `/api/propiedades/{propiedadId}/servicios/{servicioId}` | JWT |
| `GET` | `/api/servicios/{servicioId}/propiedades` | Pública |

### Favoritos

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/favoritos` | JWT |
| `POST` | `/api/favoritos` | JWT |
| `GET` | `/api/usuarios/{id}/favoritos` | JWT |
| `DELETE` | `/api/favoritos/propiedad/{propiedad_id}` | JWT |

### Consultas y mensajes

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/consultas` | JWT |
| `POST` | `/api/consultas` | JWT |
| `GET` | `/api/consultas/{id}` | JWT |
| `PUT` | `/api/consultas/{id}` | JWT |
| `DELETE` | `/api/consultas/{id}` | JWT |
| `POST` | `/api/consultas/{id}/restaurar` | JWT |
| `GET` | `/api/consultas/propiedad/{propiedadId}` | JWT |
| `GET` | `/api/consultas/usuario/{usuarioId}` | JWT |
| `GET` | `/api/consultas/recibidas` | JWT |
| `GET` | `/api/admin/consultas` | JWT; administración |
| `GET` | `/api/consultas/{consulta_id}/mensajes` | JWT |
| `POST` | `/api/consultas/{consulta_id}/mensajes` | JWT |

### Reservas

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/reservas` | JWT |
| `POST` | `/api/reservas` | JWT |
| `GET` | `/api/reservas/{id}` | JWT |
| `PUT` | `/api/reservas/{id}` | JWT |
| `PUT` | `/api/reservas/{id}/confirmar` | JWT |
| `PUT` | `/api/reservas/{id}/rechazar` | JWT |
| `PUT` | `/api/reservas/{id}/finalizar` | JWT |
| `PUT` | `/api/reservas/{id}/cancelar` | JWT |
| `DELETE` | `/api/reservas/{id}` | JWT |
| `POST` | `/api/reservas/{id}/restaurar` | JWT |
| `POST` | `/api/admin/reservas` | JWT; administración |

### Reseñas

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/resenas` | Pública |
| `POST` | `/api/resenas` | JWT |
| `GET` | `/api/resenas/{id}` | Pública |
| `PUT` | `/api/resenas/{id}` | JWT |
| `DELETE` | `/api/resenas/{id}` | JWT |
| `POST` | `/api/resenas/{id}/restaurar` | JWT; administración |
| `GET` | `/api/resenas/reserva/{reservaId}` | Pública |
| `GET` | `/api/resenas/propiedad/{propiedadId}` | Pública |
| `GET` | `/api/resenas/usuario/{usuarioId}` | Pública |
| `GET` | `/api/resenas/calificador/{calificadorId}` | Pública |
| `GET` | `/api/resenas/mis-propiedades` | JWT |
| `GET` | `/api/resenas/como-inquilino` | JWT |
| `POST` | `/api/admin/resenas` | JWT; administración |

### Notificaciones y logs

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/notificaciones` | JWT |
| `GET` | `/api/notificaciones/no-leidas` | JWT |
| `PUT` | `/api/notificaciones/{id}/leer` | JWT |
| `PUT` | `/api/notificaciones/leer-todas` | JWT |
| `GET` | `/api/logs-actividad` | JWT; administración |
| `GET` | `/api/logs-actividad/{id}` | JWT; administración |

Los logs se generan como parte de las operaciones del sistema; el API solo
expone las rutas de consulta indicadas.
