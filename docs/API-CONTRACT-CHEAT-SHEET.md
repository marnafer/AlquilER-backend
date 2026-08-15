# Hoja de Referencia rápida — API Contract AlquilER

> Resumen operativo del contrato API, sin ejemplos de request/response ni repeticiones de errores estándar.

---

## 1) Autenticación

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `POST /api/autenticador/login` | Inicia sesión con email y contraseña. | No | • Emite JWT con `rol_id`; • credenciales inválidas => `401` |
| `POST /api/autenticador/register` | Registra un nuevo usuario. | No | • Email único; • rol por defecto usuario |
| `POST /api/autenticador/logout` | Cierra sesión del cliente. | Sí - Usuario | • Requiere JWT válido; • no se invalida sesión en servidor si no hay blacklist |

---

## 2) Usuarios

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/usuarios` | Lista usuarios del sistema. | Sí - Admin | • Solo admin; • no expone password |
| `GET /api/usuarios/{id}` | Consulta un usuario específico. | Sí - Usuario/Admin | • Usuario solo ve su propio perfil; • admin ve cualquiera |
| `GET /api/usuarios/me` | Devuelve el usuario autenticado. | Sí - Usuario | • Identifica por JWT; • no expone password |
| `PUT /api/usuarios/{id}` | Actualiza datos del usuario. | Sí - Usuario/Admin | • No permite cambiar `rol_id`; • email debe ser único |
| `DELETE /api/usuarios/{id}` | Elimina lógica del usuario. | Sí - Usuario/Admin | • SoftDeletes; • usuario propio o admin |
| `POST /api/usuarios/{id}/restaurar` | Restaura usuario eliminado. | Sí - Admin | • SoftDeletes; • solo admin |

---

## 3) Propiedades

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/propiedades` | Lista propiedades públicas. | No | • Excluye eliminadas por SoftDeletes; • filtros opcionales |
| `GET /api/propiedades/{id}` | Consulta una propiedad. | No | • Requiere `id` positivo; • no devuelve propiedades eliminadas |
| `POST /api/propiedades` | Crea propiedad del usuario autenticado. | Sí - Usuario | • `usuario_id` se toma del JWT; • no se acepta desde cliente |
| `PUT /api/propiedades/{id}` | Actualiza propiedad. | Sí - Usuario/Admin | • Solo dueño o admin; • no permite cambiar `usuario_id` |
| `DELETE /api/propiedades/{id}` | Elimina lógica de la propiedad. | Sí - Usuario/Admin | • SoftDeletes; • solo dueño o admin |
| `POST /api/propiedades/{id}/restaurar` | Restaura propiedad eliminada. | Sí - Usuario/Admin | • SoftDeletes; • solo dueño o admin |

---

## 4) Imágenes de propiedades

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/propiedad-imagenes` | Lista imágenes. | No | • Excluye imágenes de propiedades eliminadas; • filtros opcionales |
| `GET /api/propiedad-imagenes/{id}` | Consulta una imagen. | No | • `id` positivo; • no devuelve imágenes de propiedades eliminadas |
| `POST /api/propiedad-imagenes` | Crea imagen para una propiedad. | Sí - Usuario/Admin | • Debe pertenecer a propiedad válida; • una sola imagen principal por propiedad |
| `PUT /api/propiedad-imagenes/{id}/principal` | Define imagen principal. | Sí - Usuario/Admin | • Desactiva la principal anterior; • solo dueño o admin |
| `DELETE /api/propiedad-imagenes/{id}` | Elimina imagen. | Sí - Usuario/Admin | • Si era principal, queda sin principal; • no auto-asigna otra |

---

## 5) Categorías

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/categorias` | Lista categorías. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/categorias/{id}` | Consulta categoría. | No | • `id` positivo; • categoría eliminada no visible |
| `POST /api/categorias` | Crea categoría. | Sí - Admin | • Unicidad por nombre; • solo admin |
| `PUT /api/categorias/{id}` | Actualiza categoría. | Sí - Admin | • No modifica `id`; • no permite duplicados |
| `DELETE /api/categorias/{id}` | Elimina categoría. | Sí - Admin | • 409 si está asociada a propiedades; • SoftDeletes |
| `POST /api/categorias/{id}/restaurar` | Restaura categoría. | Sí - Admin | • SoftDeletes; • solo si estaba eliminada |

---

## 6) Provincias

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/provincias` | Lista provincias. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/provincias/{id}` | Consulta provincia. | No | • `id` positivo; • provincia eliminada no visible |
| `POST /api/provincias` | Crea provincia. | Sí - Admin | • Unicidad por nombre; • solo admin |
| `PUT /api/provincias/{id}` | Actualiza provincia. | Sí - Admin | • No modifica `id`; • admite parcial o completo |
| `DELETE /api/provincias/{id}` | Elimina provincia. | Sí - Admin | • 409 si tiene localidades asociadas; • SoftDeletes |
| `POST /api/provincias/{id}/restaurar` | Restaura provincia. | Sí - Admin | • SoftDeletes; • no permite restaurar si hay conflicto de integridad |

---

## 7) Localidades

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/localidades` | Lista localidades. | No | • Excluye SoftDeletes; • filtro por `provincia_id` |
| `GET /api/localidades/{id}` | Consulta localidad. | No | • `id` positivo; • no devuelve eliminadas |
| `POST /api/localidades` | Crea localidad. | Sí - Admin | • Debe apuntar a provincia válida; • unicidad dentro de provincia |
| `PUT /api/localidades/{id}` | Actualiza localidad. | Sí - Admin | • `provincia_id` debe existir y estar activa |
| `DELETE /api/localidades/{id}` | Elimina localidad. | Sí - Admin | • 409 si tiene propiedades asociadas; • SoftDeletes |
| `POST /api/localidades/{id}/restaurar` | Restaura localidad. | Sí - Admin | • SoftDeletes; • requiere provincia activa |

---

## 8) Roles

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/roles` | Lista roles activos. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/roles/{id}` | Consulta un rol. | No | • `id` positivo; • rol eliminado no visible |
| `POST /api/roles` | Crea rol. | Sí - Admin | • No se permite nombre duplicado activo |
| `PUT /api/roles/{id}` | Actualiza rol. | Sí - Admin | • No modifica `id`; • evita conflictos de nombre |
| `DELETE /api/roles/{id}` | Elimina rol. | Sí - Admin | • 409 si tiene usuarios asociados; • SoftDeletes |
| `POST /api/roles/{id}/restaurar` | Restaura rol. | Sí - Admin | • SoftDeletes; • no permite duplicar nombre activo |

---

## 9) Favoritos

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/favoritos` | Lista favoritos del usuario autenticado. | Sí - Usuario | • Solo propios; • excluye propiedades eliminadas |
| `POST /api/favoritos` | Agrega propiedad a favoritos. | Sí - Usuario | • `usuario_id` proviene del JWT; • no duplicados |
| `DELETE /api/favoritos/propiedad/{propiedad_id}` | Quita una propiedad de favoritos. | Sí - Usuario | • Solo favorito propio; • no elimina la propiedad |
| `GET /api/usuarios/{id}/favoritos` | Consulta favoritos de un usuario. | Sí - Usuario/Admin | • Usuario propio o admin; • no incluye propiedades eliminadas |

---

## 10) Consultas

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/consultas` | Lista consultas visibles al usuario. | Sí - Usuario/Admin | • Usuario solo ve sus consultas o de sus propiedades; • SoftDeletes |
| `GET /api/consultas/{id}` | Consulta una consulta específica. | Sí - Usuario/Admin | • Permisos por autor/propietario/admin |
| `POST /api/consultas` | Crea una consulta sobre una propiedad. | Sí - Usuario | • `usuario_id` viene del JWT; • propiedad debe existir |
| `PUT /api/consultas/{id}` | Actualiza mensaje de la consulta. | Sí - Usuario/Admin | • Solo autor o admin; • no modifica `usuario_id` ni `propiedad_id` |
| `DELETE /api/consultas/{id}` | Elimina lógica la consulta. | Sí - Usuario/Admin | • SoftDeletes; • no elimina propiedad ni usuario |
| `POST /api/consultas/{id}/restaurar` | Restaura consulta eliminada. | Sí - Usuario/Admin | • SoftDeletes; • propiedad asociada debe seguir activa |

---

## 11) Reservas

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/reservas` | Lista reservas del usuario o propiedad. | Sí - Usuario/Admin | • Participa como inquilino, propietario o admin; • SoftDeletes |
| `GET /api/reservas/{id}` | Consulta una reserva. | Sí - Usuario/Admin | • Permisos por participación; • reserva eliminada no visible |
| `POST /api/reservas` | Solicita una reserva. | Sí - Usuario | • Estado inicial `pendiente`; • propiedad debe estar disponible |
| `PUT /api/reservas/{id}` | Actualiza fechas de la reserva. | Sí - Usuario/Admin | • No cambia `usuario_id`, `propiedad_id`, `estado`; • valida disponibilidad |
| `DELETE /api/reservas/{id}` | Elimina lógica la reserva. | Sí - Usuario/Admin | • SoftDeletes; • solo autor o admin |
| `POST /api/reservas/{id}/restaurar` | Restaura reserva eliminada. | Sí - Usuario/Admin | • SoftDeletes; • revalida disponibilidad y compatibilidad |

> Flujos de estado: `pendiente → confirmada → finalizada`, `pendiente → rechazada`, `pendiente → cancelada`.

---

## 12) Reseñas

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/resenas` | Lista reseñas públicas no eliminadas. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/resenas/{id}` | Consulta una reseña puntual. | No | • `id` positivo; • si está eliminada, no visible |
| `POST /api/resenas` | Crea una reseña para una propiedad. | Sí - Usuario | • `usuario_id` del JWT; • una reseña activa por usuario y propiedad; • calificación 1–5 |
| `PUT /api/resenas/{id}` | Actualiza calificación/comentario. | Sí - Usuario/Admin | • Solo autor o admin; • no cambia usuario ni propiedad |
| `DELETE /api/resenas/{id}` | Elimina lógica la reseña. | Sí - Usuario/Admin | • SoftDeletes; • no elimina propiedad ni usuario |
| `POST /api/resenas/{id}/restaurar` | Restaura reseña eliminada. | Sí - Usuario/Admin | • SoftDeletes; • valida unicidad activa |

---

## 13) Servicios

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/servicios` | Lista servicios activos. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/servicios/{id}` | Consulta un servicio específico. | No | • `id` positivo; • servicio eliminado no visible |
| `POST /api/servicios` | Crea un servicio administrativo. | Sí - Admin | • Nombre único según validación; • solo admin |
| `PUT /api/servicios/{id}` | Actualiza servicio. | Sí - Admin | • No cambia `id`; • evita duplicados |
| `DELETE /api/servicios/{id}` | Elimina lógica el servicio. | Sí - Admin | • 409 si está asociado a propiedades; • SoftDeletes |
| `POST /api/servicios/{id}/restaurar` | Restaura servicio eliminado. | Sí - Admin | • SoftDeletes; • respeta integridad de asociaciones |

---

## 14) Servicios de propiedades

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/propiedades/{propiedad_id}/servicios` | Lista servicios de una propiedad. | No | • Propiedad activa; • no devuelve servicios eliminados |
| `POST /api/propiedades/{propiedad_id}/servicios` | Asocia uno o más servicios a una propiedad. | Sí - Usuario/Admin | • Solo dueño o admin; • no repetir el mismo servicio en la misma propiedad |
| `DELETE /api/propiedades/{propiedad_id}/servicios/{servicio_id}` | Desasocia un servicio de una propiedad. | Sí - Usuario/Admin | • Elimina relación intermedia; • no elimina el servicio base |
| `PUT /api/propiedades/{propiedad_id}/servicios` | Reemplaza el conjunto de servicios de una propiedad. | Sí - Usuario/Admin | • Lista final definida por cliente; • elimina faltantes y mantiene existentes |

> Regla de integridad: la entidad `propiedad_servicio` es una relación intermedia, no un recurso con SoftDeletes propio.

---

## 15) Logs de actividad

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/logs-actividad` | Lista historial de actividad del sistema. | Sí - Admin | • Solo admins; • registros históricos, ordenados por fecha |
| `GET /api/logs-actividad/{id}` | Consulta un log puntual. | Sí - Admin | • `id` positivo; • solo lectura |
| `No expuesto` | Crear/editar/eliminar/restaurar logs | Interno del sistema | • Generados automáticamente; • inmutables desde la API |

> Regla de negocio: los logs de actividad son históricos e inmutables. No deben ser creados ni modificados por el cliente; solo consultados por administradores.

---

## Entidades faltantes del contrato (16-18)

### 16) Servicios

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/servicios` | Lista servicios activos. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/servicios/{id}` | Consulta un servicio específico. | No | • `id` positivo; • servicio eliminado no visible |
| `POST /api/servicios` | Crea un servicio administrativo. | Sí - Admin | • Nombre único según validación; • solo admin |
| `PUT /api/servicios/{id}` | Actualiza servicio. | Sí - Admin | • No cambia `id`; • evita duplicados |
| `DELETE /api/servicios/{id}` | Elimina lógica el servicio. | Sí - Admin | • 409 si está asociado a propiedades; • SoftDeletes |
| `POST /api/servicios/{id}/restaurar` | Restaura servicio eliminado. | Sí - Admin | • SoftDeletes; • respeta integridad de asociaciones |

### 17) Servicios de propiedades

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/propiedades/{propiedad_id}/servicios` | Lista servicios de una propiedad. | No | • Propiedad activa; • no devuelve servicios eliminados |
| `POST /api/propiedades/{propiedad_id}/servicios` | Asocia uno o más servicios a una propiedad. | Sí - Usuario/Admin | • Solo dueño o admin; • no repetir el mismo servicio en la misma propiedad |
| `DELETE /api/propiedades/{propiedad_id}/servicios/{servicio_id}` | Desasocia un servicio de una propiedad. | Sí - Usuario/Admin | • Elimina relación intermedia; • no elimina el servicio base |
| `PUT /api/propiedades/{propiedad_id}/servicios` | Reemplaza el conjunto de servicios de una propiedad. | Sí - Usuario/Admin | • Lista final definida por cliente; • elimina faltantes y mantiene existentes |

> Regla de integridad: `propiedad_servicio` es una tabla intermedia y no usa SoftDeletes propio.

### 18) Logs de actividad

| Endpoint | Acción | Auth & Rol | Reglas de Negocio Clave |
|---|---|---|---|
| `GET /api/logs-actividad` | Lista historial del sistema. | Sí - Admin | • Solo admins; • orden por fecha |
| `GET /api/logs-actividad/{id}` | Consulta un log puntual. | Sí - Admin | • `id` positivo; • solo lectura |
| `No expuesto` | Crear/editar/eliminar/restaurar logs | Interno del sistema | • Generados automáticamente; • inmutables desde la API |

> Regla de negocio: los logs son históricos e inmutables; solo se consultan mediante permisos administrativos.

---

## Resumen ejecutivo

- Público: autenticador, propiedades, categorías, provincias, localidades, roles, servicios, reseñas y lecturas públicas de relaciones.
- Requiere JWT: usuarios, favoritos, consultas, reservas, reseñas, servicios administrativos, gestión de propiedades y asociaciones.
- Administrador: usuarios, categorías, provincias, localidades, roles, servicios, logs y cualquier operación sensible o global.
- SoftDeletes: ampliamente usado en usuarios, propiedades, categorías, provincias, localidades, roles, servicios, consultas, reservas, reseñas y otros recursos de baja.
- Reglas de integridad clave: unicidad, autorización por propietario, relaciones intermedias, y flujos de estado en reservas.
