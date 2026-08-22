# Documentacion — API Contract AlquilER

> Documento operativo unificado. Contiene las reglas generales y la referencia rápida de todos los endpoints del sistema.

---

## 1. Información General
* **Sistema:** AlquilER (Gestión y alquiler de propiedades).
* **Roles (`rol_id`):** `1` (Usuario) y `2` (Administrador). 
* **Regla de Negocio Base:** El rol no determina si es propietario o inquilino. Un mismo usuario puede publicar, alquilar, reservar y reseñar sin necesidad de cuentas separadas.

## 2. Autenticación y Formato
* **Formato:** JSON, UTF-8. Métodos: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`.
* **Seguridad:** Tokens JWT requeridos en endpoints protegidos.
* **Header requerido:** `Authorization: Bearer {token}`

## 3. Convenciones de Respuesta y Arquitectura

**Estructura Estándar:**
* **Éxito:** `{ "success": true, "data": {...}, "message": "..." }` *(Para colecciones, `data` incluye `items` y `total`)*.
* **Error:** `{ "success": false, "error": "...", "validation_errors": {...} }`

**Códigos HTTP:**
* `200` OK | `201` Created
* `400` Bad Request | `401` Unauthorized | `403` Forbidden  
* `404` Not Found | `405` Method Not Allowed
* `409` Conflict (Restricciones de integridad) | `422` Unprocessable Entity (Validación) | `500` Internal Error

**Arquitectura de Eliminación:**
* **SoftDeletes:** Aplicado por defecto a entidades principales (Usuarios, Propiedades, Categorías, Reservas, etc.). Las peticiones `GET` públicas excluyen registros eliminados automáticamente.
* **Eliminación Física:** Usada estrictamente en Relaciones Intermedias (ej. Propiedad-Servicio), Favoritos y Reseñas.

---

## 4. Autenticador

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `POST /api/autenticador/login` | Inicia sesión. | No | • Emite JWT; • credenciales inválidas => `401` |
| `POST /api/autenticador/register` | Registra usuario. | No | • Email único; • rol por defecto: usuario |
| `POST /api/autenticador/logout` | Cierra sesión. | Sí - Usuario | • Requiere JWT válido |

---

## 5. Usuarios

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/usuarios` | Lista usuarios. | Sí - Admin | • Solo admin; • no expone password |
| `GET /api/usuarios/{id}` | Consulta un usuario. | Sí - Usr/Admin | • Usuario solo ve su perfil; • admin ve todos |
| `GET /api/usuarios/me` | Devuelve usuario actual. | Sí - Usuario | • Identifica por JWT |
`PUT /api/usuarios/{id}` | Actualiza datos. | Sí - Usr/Admin | • Admite actualización parcial o total; • no permite cambiar `rol_id`; • email único |
| `DELETE /api/usuarios/{id}` | Elimina usuario. | Sí - Usr/Admin | • SoftDeletes; • propio o admin |
| `POST /api/usuarios/{id}/restaurar` | Restaura usuario. | Sí - Admin | • SoftDeletes; • solo admin |
**Actualización de usuarios:** El endpoint `PUT /api/usuarios/{id}` admite tanto actualizaciones parciales como totales. Los campos omitidos conservan su valor actual. El campo `rol_id` no puede modificarse mediante este endpoint.
---

## 6. Propiedades

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/propiedades` | Lista propiedades. | No | • Excluye SoftDeletes; • filtros opcionales |
| `GET /api/propiedades/{id}` | Consulta propiedad. | No | • `id` positivo; • eliminadas devuelven `404` |
| `POST /api/propiedades` | Crea propiedad. | Sí - Usuario | • `usuario_id` tomado del JWT |
| `PUT /api/propiedades/{id}` | Actualiza propiedad. | Sí - Usr/Admin | • Solo dueño o admin; • no modifica `usuario_id` |
| `DELETE /api/propiedades/{id}` | Elimina propiedad. | Sí - Usr/Admin | • SoftDeletes; • solo dueño o admin |
| `POST /api/propiedades/{id}/restaurar`| Restaura propiedad. | Sí - Usr/Admin | • SoftDeletes; • solo dueño o admin |

---

## 7. Imágenes de propiedades

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/propiedad-imagenes` | Lista imágenes. | No | • Excluye imágenes de propiedades eliminadas |
| `GET /api/propiedad-imagenes/{id}` | Consulta imagen. | No | • `id` positivo |
| `POST /api/propiedad-imagenes` | Crea imagen. | Sí - Usr/Admin | • Una sola imagen principal por propiedad |
| `PUT /.../{id}/principal` | Define principal. | Sí - Usr/Admin | • Desactiva la principal anterior |
| `DELETE /api/propiedad-imagenes/{id}`| Elimina imagen. | Sí - Usr/Admin | • Si era principal, queda sin principal |

---

## 8. Categorías

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/categorias` | Lista categorías. | No | • Excluye SoftDeletes |
| `GET /api/categorias/{id}` | Consulta categoría. | No | • Eliminadas devuelven `404` |
| `POST /api/categorias` | Crea categoría. | Sí - Admin | • Unicidad por nombre |
| `PUT /api/categorias/{id}` | Actualiza categoría. | Sí - Admin | • No modifica `id`; • evita duplicados |
| `DELETE /api/categorias/{id}` | Elimina categoría. | Sí - Admin | • `409 Conflict` si tiene propiedades; • SoftDeletes |
| `POST /api/categorias/{id}/restaurar` | Restaura categoría. | Sí - Admin | • SoftDeletes |

---

## 9. Provincias

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/provincias` | Lista provincias. | No | • Excluye SoftDeletes |
| `GET /api/provincias/{id}` | Consulta provincia. | No | • Eliminadas devuelven `404` |
| `POST /api/provincias` | Crea provincia. | Sí - Admin | • Unicidad por nombre |
| `PUT /api/provincias/{id}` | Actualiza provincia. | Sí - Admin | • Admite actualización parcial |
| `DELETE /api/provincias/{id}` | Elimina provincia. | Sí - Admin | • `409 Conflict` si tiene localidades; • SoftDeletes |
| `POST /api/provincias/{id}/restaurar` | Restaura provincia. | Sí - Admin | • SoftDeletes |

---

## 10. Localidades

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/localidades` | Lista localidades. | No | • Filtro opcional por `provincia_id` |
| `GET /api/localidades/{id}` | Consulta localidad. | No | • Eliminadas devuelven `404` |
| `POST /api/localidades` | Crea localidad. | Sí - Admin | • Unicidad dentro de la misma provincia |
| `PUT /api/localidades/{id}` | Actualiza localidad. | Sí - Admin | • `provincia_id` debe existir y estar activa |
| `DELETE /api/localidades/{id}` | Elimina localidad. | Sí - Admin | • `409 Conflict` si tiene propiedades; • SoftDeletes |
| `POST /api/localidades/{id}/restaurar`| Restaura localidad. | Sí - Admin | • `409 Conflict` si su provincia está eliminada |

---

## 11. Roles

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/roles` | Lista roles. | No | • Excluye SoftDeletes |
| `GET /api/roles/{id}` | Consulta un rol. | No | • Eliminados devuelven `404` |
| `POST /api/roles` | Crea rol. | Sí - Admin | • Nombre no debe existir en activos |
| `PUT /api/roles/{id}` | Actualiza rol. | Sí - Admin | • Evita conflictos de nombre |
| `DELETE /api/roles/{id}` | Elimina rol. | Sí - Admin | • `409 Conflict` si tiene usuarios; • SoftDeletes |
| `POST /api/roles/{id}/restaurar` | Restaura rol. | Sí - Admin | • `409 Conflict` si hay rol activo con mismo nombre |

---

## 12. Favoritos

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/favoritos` | Lista favs del usuario. | Sí - Usuario | • Excluye propiedades eliminadas |
| `POST /api/favoritos` | Agrega a favoritos. | Sí - Usuario | • `usuario_id` del JWT; • `409 Conflict` si ya existe |
| `DELETE /api/favoritos/prop/{id}` | Quita de favoritos. | Sí - Usuario | • Eliminación FÍSICA de la relación |
| `GET /api/usuarios/{id}/favoritos` | Consulta favs de otro. | Sí - Usr/Admin | • Admin ve todos, usuario solo los suyos |

---

## 13. Consultas

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/consultas` | Lista consultas. | Sí - Usr/Admin | • Usuario ve emitidas/recibidas; • SoftDeletes |
| `GET /api/consultas/{id}` | Consulta específica. | Sí - Usr/Admin | • Permisos por autor/propietario/admin |
| `POST /api/consultas` | Crea consulta. | Sí - Usuario | • `usuario_id` del JWT |
| `PUT /api/consultas/{id}` | Actualiza mensaje. | Sí - Usr/Admin | • Solo autor o admin |
| `DELETE /api/consultas/{id}` | Elimina consulta. | Sí - Usr/Admin | • SoftDeletes; • no elimina propiedad ni usuario |
| `POST /api/consultas/{id}/restaurar`| Restaura consulta. | Sí - Usr/Admin | • `409 Conflict` si la propiedad fue eliminada |

---

## 14. Reservas

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/reservas` | Lista reservas. | Sí - Usr/Admin | • Participación como inquilino o propietario |
| `GET /api/reservas/{id}` | Consulta reserva. | Sí - Usr/Admin | • Eliminadas devuelven `404` |
| `POST /api/reservas` | Solicita reserva. | Sí - Usuario | • Estado inicial `pendiente`; • valida disponibilidad |
| `PUT /api/reservas/{id}` | Actualiza fechas. | Sí - Usr/Admin | • No altera `estado`; • valida disponibilidad `409` |
| `DELETE /api/reservas/{id}` | Elimina reserva. | Sí - Usr/Admin | • SoftDeletes; • solo autor o admin |
| `POST /api/reservas/{id}/restaurar`| Restaura reserva. | Sí - Usr/Admin | • Revalida disponibilidad `409` |

> **Flujos de estado:** `pendiente → confirmada → finalizada`, `pendiente → rechazada`, `pendiente → cancelada`.

---

## 15. Reseñas

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/resenas` | Lista reseñas. | No | • Información pública |
| `GET /api/resenas/{id}` | Consulta reseña. | No | • `404` si no existe |
| `POST /api/resenas` | Crea reseña. | Sí - Usuario | • Requiere reserva finalizada; • Unicidad (1 por reserva) `409` |
| `PUT /api/resenas/{id}` | Actualiza texto/nota. | Sí - Usr/Admin | • Solo autor o admin; • calificación 1-5 |
| `DELETE /api/resenas/{id}` | Elimina reseña. | Sí - Usr/Admin | • Eliminación FÍSICA (Sin SoftDeletes) |

---

## 16. Servicios

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/servicios` | Lista servicios activos. | No | • Excluye SoftDeletes |
| `GET /api/servicios/{id}` | Consulta servicio. | No | • Eliminados devuelven `404` |
| `POST /api/servicios` | Crea servicio. | Sí - Admin | • Nombre único |
| `PUT /api/servicios/{id}` | Actualiza servicio. | Sí - Admin | • Evita duplicados |
| `DELETE /api/servicios/{id}` | Elimina servicio. | Sí - Admin | • `409 Conflict` si está asociado a propiedades; • SoftDeletes |
| `POST /api/servicios/{id}/restaurar`| Restaura servicio. | Sí - Admin | • Respeta integridad de asociaciones `409` |

---

## 17. Servicios de Propiedades (Asociaciones)

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/propiedades/{id}/servicios` | Lista servicios. | No | • Propiedad debe estar activa |
| `POST /api/propiedades/{id}/servicios`| Asocia servicios. | Sí - Usr/Admin | • Dueño o admin; • no duplicar el mismo servicio |
| `DELETE /.../{p_id}/servicios/{s_id}` | Desasocia servicio. | Sí - Usr/Admin | • Eliminación FÍSICA de la tabla intermedia |
| `PUT /api/propiedades/{id}/servicios` | Sincroniza servicios. | Sí - Usr/Admin | • Reemplaza lista: crea nuevas, elimina omitidas |

> **Regla de integridad:** `propiedad_servicio` es una tabla pivote intermedia y no utiliza SoftDeletes.

---

## 18. Logs de Actividad

| Endpoint | Acción | Auth & Rol | Reglas Clave |
|---|---|---|---|
| `GET /api/logs-actividad` | Lista historial. | Sí - Admin | • Orden cronológico; • exclusivo administradores |
| `GET /api/logs-actividad/{id}` | Consulta log puntual. | Sí - Admin | • Solo lectura |
| *No expuestos* | CRUD manual | N/A | • Logs autogenerados por el sistema, inmutables |