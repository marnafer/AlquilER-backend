# Contrato de API — AlquilER

## 1. Información general

### 1.1 Descripción

API REST para el sistema de gestión y alquiler de propiedades **AlquilER**.

La API permite:

* Registro y autenticación de usuarios.
* Gestión de usuarios.
* Consulta y gestión de propiedades.
* Gestión de imágenes de propiedades.
* Gestión de favoritos.
* Consultas y conversaciones entre usuarios.
* Gestión de reservas.
* Gestión de reseñas.
* Gestión de categorías, provincias y localidades.
* Gestión de servicios ofrecidos por las propiedades.
* Administración y consulta de registros de actividad.

### 1.2 Base URL

Durante el desarrollo:

`http://localhost/AlquilER-backend/api`

La URL de producción será definida posteriormente.

### 1.3 Formato

La API utiliza:

* HTTP/HTTPS.
* JSON para las solicitudes y respuestas.
* UTF-8.
* Autenticación mediante JWT.
* Métodos HTTP estándar: `GET`, `POST`, `PUT`, `PATCH` y `DELETE`.

### 1.4 Identificación de roles

El sistema utiliza únicamente dos roles:

| `rol_id` | Rol           | Descripción                                                                                             |
| -------: | ------------- | ------------------------------------------------------------------------------------------------------- |
|        1 | Usuario       | Usuario autenticado del sistema. Puede utilizar las funcionalidades disponibles para cualquier usuario. |
|        2 | Administrador | Usuario con permisos administrativos. También puede utilizar las funcionalidades de un usuario común.   |

El rol de una persona **no determina si es propietario o inquilino**.

Un mismo usuario puede:

* Publicar propiedades.
* Alquilar propiedades de otros usuarios.
* Realizar consultas.
* Responder consultas relacionadas con sus propiedades.
* Realizar reservas.
* Dejar reseñas.
* Utilizar favoritos.

Por lo tanto, **no es necesario crear dos cuentas para una misma persona**.

---

## 2. Autenticación

Las operaciones que requieren autenticación utilizan un token JWT.

El cliente debe enviar el token mediante el encabezado:

`Authorization: Bearer {token}`

### 2.1 Operaciones públicas

No requieren autenticación.

Actualmente:

* `POST /autenticador/login`
* `POST /autenticador/register`
* `GET /propiedades`
* `GET /propiedades/{id}`
* Consultas públicas de información que sean definidas explícitamente como tales.

### 2.2 Operaciones autenticadas

Requieren un JWT válido.

El usuario debe estar autenticado para realizar acciones como:

* Gestionar sus propiedades.
* Gestionar favoritos.
* Crear consultas.
* Participar en conversaciones.
* Crear reservas.
* Crear reseñas.
* Modificar sus datos personales.

### 2.3 Operaciones administrativas

Requieren un JWT válido y `rol_id = 2`.

Estas operaciones están destinadas exclusivamente a administradores, por ejemplo:

* Gestión de usuarios.
* Gestión de categorías.
* Gestión de provincias.
* Gestión de localidades.
* Gestión de servicios.
* Gestión de logs.
* Operaciones administrativas sobre reservas, consultas u otras entidades según corresponda.

---

## 3. Convención general de respuestas

Todas las respuestas de la API deberán mantener una estructura consistente.

### 3.1 Respuesta exitosa

```json
{
    "success": true,
    "data": {},
    "message": "Operación realizada correctamente"
}
```

Cuando se devuelve una colección:

```json
{
    "success": true,
    "data": {
        "items": [],
        "total": 0
    },
    "message": "Operación realizada correctamente"
}
```

### 3.2 Respuesta de error

```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": {}
}
```

`errors` podrá contener información adicional de validación cuando corresponda.

### 3.3 Códigos HTTP

| Código | Significado                     |
| -----: | ------------------------------- |
|    200 | Operación exitosa               |
|    201 | Recurso creado                  |
|    400 | Solicitud incorrecta            |
|    401 | No autenticado / token inválido |
|    403 | Autenticado pero sin permisos   |
|    404 | Recurso no encontrado           |
|    422 | Error de validación             |
|    500 | Error interno del servidor      |

# 4. Autenticación y autorización

## 4.1. Conceptos generales

La API utiliza autenticación mediante **JWT (JSON Web Token)**.

Los endpoints pueden clasificarse en:

- **Públicos:** no requieren autenticación.
- **Autenticados:** requieren un token JWT válido.
- **Administrativos:** requieren autenticación y rol de administrador.

### Roles

| `rol_id` | Rol | Descripción |
|---:|---|---|
| `1` | Usuario | Usuario común de la aplicación |
| `2` | Administrador | Usuario con permisos administrativos |

> El administrador también puede realizar las operaciones disponibles para un usuario autenticado. El rol administrativo agrega permisos, no los reemplaza.

## 4.2. Envío del token

Los endpoints que requieren autenticación deben recibir el token JWT mediante el header HTTP `Authorization`.

### Formato

    Authorization: Bearer {token}

### Ejemplo

    GET /api/usuarios/me
    Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

El token es generado al iniciar sesión y debe ser enviado en cada solicitud a un endpoint protegido.

Si el token no existe, es inválido o está expirado, la API responderá con:

**HTTP `401 Unauthorized`**

    {
      "success": false,
      "message": "Token requerido"
    }

o:

    {
      "success": false,
      "message": "Token inválido o expirado"
    }

## 4.3. Login

### `POST /api/autenticador/login`

Permite autenticar a un usuario mediante su correo electrónico y contraseña.

**Autenticación requerida:** No.

### Request

    {
      "email": "usuario@email.com",
      "contrasena": "123456"
    }

### Response `200 OK`

    {
      "success": true,
      "message": "Login exitoso",
      "data": {
        "token": "eyJhbGciOiJIUzI1NiIs...",
        "usuario": {
          "id": 1,
          "nombre": "Juan",
          "apellido": "Perez",
          "email": "usuario@email.com",
          "rol_id": 1
        }
      }
    }

### Credenciales incorrectas

**HTTP `401 Unauthorized`**

    {
      "success": false,
      "message": "Credenciales inválidas"
    }

### Datos inválidos

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "email": "El email es requerido",
        "contrasena": "La contraseña es requerida"
      }
    }

### Consideraciones

- El endpoint no requiere un token JWT.
- El usuario debe proporcionar un email y una contraseña válidos.
- Si las credenciales son correctas, la API genera un JWT.
- El JWT debe enviarse posteriormente en el header `Authorization` para acceder a endpoints protegidos.
- La respuesta incluye el `rol_id` del usuario autenticado para que el frontend pueda conocer sus permisos.
- La contraseña nunca debe devolverse en la respuesta.

## 4.4. Registro

### `POST /api/autenticador/register`

Permite registrar un nuevo usuario en el sistema.

**Autenticación requerida:** No.

### Request

    {
      "nombre": "Juan",
      "apellido": "Perez",
      "email": "usuario@email.com",
      "telefono": "3431234567",
      "domicilio": "Av. Principal 123",
      "contrasena": "123456"
    }

### Campos

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `nombre` | string | Sí | Nombre del usuario. Entre 2 y 50 caracteres. |
| `apellido` | string | Sí | Apellido del usuario. Entre 2 y 50 caracteres. |
| `email` | string | Sí | Correo electrónico válido. Máximo 100 caracteres. Debe ser único. |
| `telefono` | string | No | Número telefónico. |
| `domicilio` | string | No | Domicilio del usuario. Entre 5 y 100 caracteres. |
| `contrasena` | string | Sí | Contraseña de al menos 6 caracteres. |
| `rol_id` | integer | No | No debe ser enviado por el frontend. El sistema asigna 1 (Usuario) automáticamente. |

### Response `201 Created`

    {
      "success": true,
      "data": [],
      "message": "Usuario registrado"
    }

### Email ya registrado

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "email": "El usuario ya existe"
      }
    }

### Datos inválidos

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "nombre": "El nombre es requerido",
        "email": "El email no es válido",
        "contrasena": "Debe tener al menos 6 caracteres"
      }
    }

### Consideraciones

- El registro no requiere autenticación.
- Todo nuevo usuario se registra inicialmente con rol_id = 1.
- El frontend no debe permitir que el usuario seleccione o envíe su propio rol_id.
- El rol de administrador será asignado mediante mecanismos administrativos.
- La contraseña debe almacenarse utilizando un algoritmo de hash seguro.
- La contraseña nunca se devuelve en una respuesta de la API.
- Un usuario representa una única cuenta, independientemente de si publica propiedades, alquila propiedades o realiza ambas actividades.

## 4.5. Logout

### `POST /api/autenticador/logout`

Permite cerrar la sesión del usuario autenticado.

**Autenticación requerida:** Sí.

### Request

No requiere cuerpo (`body`).

El cliente debe enviar un JWT válido:

    Authorization: Bearer {token}

### Response `200 OK`

    {
      "success": true,
      "data": [],
      "message": "Logout (el cliente elimina el token)"
    }

### Consideraciones

- El endpoint requiere un JWT válido.
- Actualmente la API no mantiene una lista de tokens revocados.
- El logout se realiza del lado del cliente eliminando el token JWT almacenado.
- Una vez eliminado el token, el cliente deberá autenticarse nuevamente para obtener acceso a endpoints protegidos.
- El servidor no invalida ni modifica el JWT existente.
- Si el token enviado es inválido, está expirado o no se proporciona, la API responderá con `401 Unauthorized`.

## 5. Gestión de usuarios

### 5.1. Obtener usuarios

#### `GET /api/usuarios`

Permite obtener el listado de usuarios registrados en el sistema.

**Autenticación requerida:** Sí.

**Permiso:** Administrador (`rol_id = 2`).

### Response `200 OK`

    {
      "success": true,
      "data": {
        "items": [],
        "total": 0
      }
    }

### Consideraciones

- El endpoint requiere un JWT válido.
- Solo los administradores pueden obtener el listado completo de usuarios.
- Los usuarios comunes no pueden consultar el listado completo de usuarios.
- La contraseña nunca debe incluirse en las respuestas.
- El rol_id permite identificar si el usuario es común (1) o administrador (2).

### 5.2. Obtener un usuario

#### `GET /api/usuarios/{id}`

Permite obtener la información de un usuario específico.

**Autenticación requerida:** Sí.

### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador del usuario. |

### Permisos

- Un usuario común puede consultar únicamente su propia información.
- Un administrador puede consultar la información de cualquier usuario.

### Response `200 OK`

    {
      "success": true,
      "data": {
        "id": 1,
        "nombre": "Juan",
        "apellido": "Perez",
        "email": "usuario@email.com",
        "telefono": "3431234567",
        "domicilio": "Av. Principal 123",
        "rol_id": 1
      }
    }

### Usuario no encontrado

**HTTP `404 Not Found`**

    {
      "success": false,
      "error": "Usuario no encontrado"
    }

### Sin permisos

**HTTP `403 Forbidden`**

    {
      "success": false,
      "error": "No tienes permiso para consultar este usuario"
    }

### 5.3. Obtener usuario autenticado

#### `GET /api/usuarios/me`

Permite obtener la información del usuario actualmente autenticado.

**Autenticación requerida:** Sí.

### Response `200 OK`

    {
      "success": true,
      "data": {
        "id": 1,
        "nombre": "Juan",
        "apellido": "Perez",
        "email": "usuario@email.com",
        "telefono": "3431234567",
        "domicilio": "Av. Principal 123",
        "rol_id": 1
      }
    }

### Consideraciones

- El usuario se identifica mediante el sub incluido en el JWT.
- No es necesario enviar el ID del usuario en la solicitud.
- La contraseña nunca se devuelve.
- Un administrador también puede utilizar este endpoint para consultar sus propios datos.

### 5.4. Actualizar usuario

#### `PUT /api/usuarios/{id}`

Permite modificar los datos de un usuario.

**Autenticación requerida:** Sí.

### Permisos

- Un usuario común puede modificar únicamente sus propios datos.
- Un administrador puede modificar los datos de cualquier usuario.
- El usuario no puede modificar su propio rol_id mediante este endpoint.

### Request

    {
      "nombre": "Juan Carlos",
      "apellido": "Perez",
      "email": "juan@email.com",
      "telefono": "3431234567",
      "domicilio": "Nueva dirección 456"
    }

### Campos

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `nombre` | string | Según validación | Nombre del usuario. |
| `apellido` | string | Según validación | Apellido del usuario. |
| `email` | string | Según validación | Correo electrónico válido y único. |
| `telefono` | string | No | Número telefónico. |
| `domicilio` | string | No | Domicilio del usuario. |
| `contrasena` | string | No | Nueva contraseña, si corresponde. |

### Response `200 OK`

    {
      "success": true,
      "data": {
        "id": 1,
        "nombre": "Juan Carlos",
        "apellido": "Perez",
        "email": "juan@email.com",
        "telefono": "3431234567",
        "domicilio": "Nueva dirección 456",
        "rol_id": 1
      }
    }

### Consideraciones

- El ID se obtiene de la URL.
- El rol_id no debe modificarse desde una actualización realizada por un usuario común.
- Las contraseñas se almacenan mediante hash y nunca se devuelven.
- Si el nuevo email ya pertenece a otro usuario, la operación debe rechazarse.

### 5.5. Eliminar usuario

#### `DELETE /api/usuarios/{id}`

Permite eliminar un usuario.

**Autenticación requerida:** Sí.

### Permisos

- Un usuario común puede eliminar únicamente su propia cuenta.
- Un administrador puede eliminar cualquier cuenta de usuario según las reglas administrativas del sistema.

### Response `200 OK`

    {
      "success": true,
      "data": [],
      "message": "Usuario eliminado exitosamente"
    }

### Usuario no encontrado

**HTTP `404 Not Found`**

    {
      "success": false,
      "error": "Usuario no encontrado"
    }

### Sin permisos

**HTTP `403 Forbidden`**

    {
      "success": false,
      "error": "No tienes permiso para eliminar este usuario"
    }

### Consideraciones

- La eliminación utiliza el mecanismo definido por el modelo de usuario.
- Si el sistema utiliza SoftDeletes, el registro no se elimina físicamente de la base de datos.
- El usuario eliminado no podrá utilizar normalmente sus credenciales hasta que sea restaurado, si dicha funcionalidad existe.
- La eliminación de un usuario no debe exponer ni devolver su contraseña.

### 5.6. Restaurar usuario

#### `POST /api/usuarios/{id}/restaurar`

Permite restaurar un usuario eliminado mediante SoftDeletes.

**Autenticación requerida:** Sí.

**Permiso:** Administrador (`rol_id = 2`).

### Response `200 OK`

    {
      "success": true,
      "data": {},
      "message": "Usuario restaurado exitosamente"
    }

### Consideraciones

- Solo puede ser ejecutado por un administrador.
- El usuario debe existir como registro eliminado.
- Si el usuario no está eliminado, la API debe informar que la operación no es válida.
- Si el usuario no existe, se responde con `404 Not Found`.

# 6. Gestión de propiedades

## 6.1. Obtener propiedades

### `GET /api/propiedades`

Permite obtener el listado de propiedades registradas en el sistema.

**Autenticación requerida:** No.

### Response `200 OK`

    {
      "success": true,
      "data": {
        "items": [],
        "total": 0
      }
    }

### Consideraciones

- El endpoint es público y no requiere autenticación.
- Devuelve las propiedades registradas en el sistema.
- Actualmente devuelve todas las propiedades mediante el modelo Propiedad.
- La respuesta incluye la colección de propiedades dentro de `data.items`.
- El campo `total` indica la cantidad de propiedades devueltas.
- Las propiedades pertenecen a un usuario, pero esto no implica que dicho usuario tenga un rol especial de propietario.
- Un usuario puede publicar propiedades y también alquilar propiedades de otros usuarios.
- La información sensible del usuario propietario no debe exponerse en este endpoint.
- Las propiedades eliminadas mediante `SoftDeletes` no se incluyen en los listados ni pueden consultarse mediante los endpoints públicos habituales.
-El endpoint permite consultar propiedades y aplicar filtros mediante parámetros de consulta.

## 6.2. Obtener una propiedad

### `GET /api/propiedades/{id}`

Permite obtener la información de una propiedad específica.

**Autenticación requerida:** No.

### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la propiedad. Debe ser un número entero positivo. |

### Response `200 OK`

    {
      "success": true,
      "data": {
          "id": 1,
          "usuario_id": 1,
          "titulo": "Casa en alquiler",
          "descripcion": "Casa amplia y luminosa.",
          "precio": 250000,
          "expensas": 50000,
          "direccion": "Av. Principal 123",
          "cantidad_ambientes": 4,
          "cantidad_dormitorios": 3,
          "cantidad_banos": 2,
          "capacidad": 6,
          "disponible": true,
          "categoria_id": 1,
          "localidad_id": 1
      }
    }

### Propiedad no encontrada

**HTTP `404 Not Found`**

    {
      "success": false,
      "message": "Propiedad no encontrada"
    }

### ID inválido

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "id": "El ID de la propiedad debe ser positivo"
      }
    }

### Consideraciones

- El endpoint es público y no requiere autenticación.
- El ID de la propiedad se obtiene desde la URL.
- El ID debe ser un número entero positivo.
- Si la propiedad no existe, la API responde con `404 Not Found`.
- La respuesta utiliza la estructura estándar definida por la API.
- La propiedad está asociada a un usuario mediante `usuario_id`.
- `usuario_id` identifica al usuario que publicó la propiedad, pero no representa un rol diferente dentro del sistema.
- Un mismo usuario puede publicar propiedades y alquilar propiedades de otros usuarios.

## 6.3. Crear propiedad

### `POST /api/propiedades`

Permite crear una nueva propiedad asociada al usuario autenticado.

**Autenticación requerida:** Sí.

### Request

    {
      "titulo": "Casa en alquiler",
      "descripcion": "Casa amplia y luminosa.",
      "precio": 250000,
      "expensas": 50000,
      "direccion": "Av. Principal 123",
      "cantidad_ambientes": 4,
      "cantidad_dormitorios": 3,
      "cantidad_banos": 2,
      "capacidad": 6,
      "disponible": true,
      "categoria_id": 1,
      "localidad_id": 1
    }

### Campos

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `titulo` | string | Sí | Título de la propiedad. |
| `descripcion` | string | Sí | Descripción de la propiedad. |
| `precio` | number | Sí | Precio de alquiler de la propiedad. |
| `expensas` | number | No | Monto de expensas, si corresponde. |
| `direccion` | string | Sí | Dirección de la propiedad. |
| `cantidad_ambientes` | integer | Sí | Cantidad de ambientes. |
| `cantidad_dormitorios` | integer | Sí | Cantidad de dormitorios. |
| `cantidad_banos` | integer | Sí | Cantidad de baños. |
| `capacidad` | integer | Sí | Capacidad máxima de personas. |
| `disponible` | boolean | Sí | Indica si la propiedad se encuentra disponible para alquiler. |
| `categoria_id` | integer | Sí | Identificador de la categoría de la propiedad. |
| `localidad_id` | integer | Sí | Identificador de la localidad donde se encuentra la propiedad. |
| `usuario_id` | integer | No | No debe ser enviado por el frontend. Se obtiene automáticamente del JWT. |

### Response `201 Created`

    {
      "success": true,
      "data": {
        "id": 1,
        "usuario_id": 1,
        "titulo": "Casa en alquiler",
        "descripcion": "Casa amplia y luminosa.",
        "precio": 250000,
        "expensas": 50000,
        "direccion": "Av. Principal 123",
        "cantidad_ambientes": 4,
        "cantidad_dormitorios": 3,
        "cantidad_banos": 2,
        "capacidad": 6,
        "disponible": true,
        "categoria_id": 1,
        "localidad_id": 1
      },
      "message": "Propiedad creada exitosamente"
    }

### JSON inválido

**HTTP `400 Bad Request`**

    {
      "success": false,
      "message": "JSON inválido"
    }

### Datos inválidos

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "titulo": "El título es requerido",
        "precio": "El precio debe ser válido"
      }
    }

### Sin autenticación

**HTTP `401 Unauthorized`**

    {
      "success": false,
      "message": "Token requerido"
    }

### Consideraciones

- El endpoint requiere un JWT válido.
- Cualquier usuario autenticado puede crear una propiedad.
- El administrador también puede utilizar este endpoint porque posee los permisos de un usuario autenticado.
- El frontend no debe enviar `usuario_id`.
- El `usuario_id` se obtiene automáticamente a partir del `sub` incluido en el JWT.
- La propiedad queda asociada al usuario que realizó la solicitud.
- No existe un rol separado de propietario.
- Un usuario puede crear múltiples propiedades.
- Un usuario puede crear propiedades y, al mismo tiempo, alquilar propiedades de otros usuarios.

## 6.4. Actualizar propiedad

### `PUT /api/propiedades/{id}`

Permite actualizar total o parcialmente los datos modificables de una propiedad.

**Autenticación requerida:** Sí.

### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la propiedad que se desea modificar. |

### Request

    {
      "titulo": "Casa en alquiler actualizada",
      "descripcion": "Casa amplia, luminosa y totalmente equipada.",
      "precio": 280000,
      "expensas": 55000,
      "direccion": "Av. Principal 456",
      "cantidad_ambientes": 5,
      "cantidad_dormitorios": 3,
      "cantidad_banos": 2,
      "capacidad": 7,
      "disponible": true,
      "categoria_id": 1,
      "localidad_id": 1
    }

### Campos

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `titulo` | string | No | Título de la propiedad. |
| `descripcion` | string | No | Descripción de la propiedad. |
| `precio` | number | No | Precio de alquiler de la propiedad. |
| `expensas` | number | No | Monto de expensas, si corresponde. |
| `direccion` | string | No | Dirección de la propiedad. |
| `cantidad_ambientes` | integer | No | Cantidad de ambientes. |
| `cantidad_dormitorios` | integer | No | Cantidad de dormitorios. |
| `cantidad_banos` | integer | No | Cantidad de baños. |
| `capacidad` | integer | No | Capacidad máxima de personas. |
| `disponible` | boolean | No | Indica si la propiedad se encuentra disponible para alquiler. |
| `categoria_id` | integer | No | Identificador de la categoría de la propiedad. |
| `localidad_id` | integer | No | Identificador de la localidad donde se encuentra la propiedad. |

### Response `200 OK`

    {
      "success": true,
      "data": {
          "id": 1,
          "usuario_id": 1,
          "titulo": "Casa en alquiler actualizada",
          "descripcion": "Casa amplia, luminosa y totalmente equipada.",
          "precio": 280000,
          "expensas": 55000,
          "direccion": "Av. Principal 456",
          "cantidad_ambientes": 5,
          "cantidad_dormitorios": 3,
          "cantidad_banos": 2,
          "capacidad": 7,
          "disponible": true,
          "categoria_id": 1,
          "localidad_id": 1
      }
    }

### JSON inválido

**HTTP `400 Bad Request`**

    {
      "success": false,
      "message": "JSON inválido"
    }

### Propiedad no encontrada

**HTTP `404 Not Found`**

    {
      "success": false,
      "message": "Propiedad no encontrada"
    }

### Sin permisos

**HTTP `403 Forbidden`**

    {
      "success": false,
      "message": "No tienes permiso para modificar esta propiedad"
    }

### Datos inválidos

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "precio": "El precio debe ser válido"
      }
    }

### Consideraciones

- El endpoint requiere un JWT válido.
- Cualquier usuario autenticado puede modificar sus propias propiedades.
- Un administrador puede modificar cualquier propiedad.
- Un usuario no puede modificar una propiedad perteneciente a otro usuario.
- El ID de la propiedad se obtiene de la URL.
- El `usuario_id` no debe ser enviado por el frontend.
- El `usuario_id` de la propiedad no se modifica durante la actualización.
- La propiedad debe existir para poder ser actualizada.
- Si el usuario autenticado no es el propietario de la propiedad y no posee permisos administrativos, la API responde con `403 Forbidden`.
- La actualización utiliza los campos permitidos por `PropiedadController`.
- Los campos son opcionales individualmente. La solicitud debe incluir al menos un campo modificable válido.
-El endpoint permite realizar tanto actualizaciones parciales como actualizaciones completas de la propiedad.
-El campo `usuario_id` no puede ser enviado ni modificado por el cliente.

## 6.5. Eliminar propiedad

### `DELETE /api/propiedades/{id}`

Permite eliminar una propiedad existente.

**Autenticación requerida:** Sí.

### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la propiedad que se desea eliminar. |

### Response `200 OK`

    {
      "success": true,
      "data": [],
      "message": "Propiedad eliminada exitosamente"
    }

### Propiedad no encontrada

**HTTP `404 Not Found`**

    {
      "success": false,
      "message": "Propiedad no encontrada"
    }

### Sin permisos

**HTTP `403 Forbidden`**

    {
      "success": false,
      "message": "No tienes permiso para eliminar esta propiedad"
    }

### ID inválido

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "id": "El ID de la propiedad debe ser positivo"
      }
    }

### Sin autenticación

**HTTP `401 Unauthorized`**

    {
      "success": false,
      "message": "Token requerido"
    }

### Consideraciones

- El endpoint requiere un JWT válido.
- Un usuario autenticado puede eliminar únicamente sus propias propiedades.
- La propiedad debe pertenecer al usuario identificado mediante el `sub` del JWT.
- Un usuario no puede eliminar una propiedad perteneciente a otro usuario.
- Un administrador puede eliminar cualquier propiedad.
- El ID de la propiedad se obtiene de la URL.
- La eliminación utiliza el mecanismo definido por el modelo `Propiedad`.
- Si el modelo utiliza `SoftDeletes`, la propiedad será marcada como eliminada y no se eliminará físicamente de la base de datos.
- Una propiedad eliminada podrá ser restaurada mediante el endpoint correspondiente, si el sistema utiliza `SoftDeletes`.
- Si la propiedad no existe, la API responde con `404 Not Found`.

## 6.6. Restaurar propiedad

### `POST /api/propiedades/{id}/restaurar`

Permite restaurar una propiedad que fue eliminada mediante `SoftDeletes`.

**Autenticación requerida:** Sí.

### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la propiedad que se desea restaurar. |

### Response `200 OK`

    {
      "success": true,
      "data": {
          "id": 1,
          "usuario_id": 1,
          "titulo": "Casa en alquiler",
          "descripcion": "Casa amplia y luminosa.",
          "precio": 250000,
          "expensas": 50000,
          "direccion": "Av. Principal 123",
          "cantidad_ambientes": 4,
          "cantidad_dormitorios": 3,
          "cantidad_banos": 2,
          "capacidad": 6,
          "disponible": true,
          "categoria_id": 1,
          "localidad_id": 1
      },
      "message": "Propiedad restaurada exitosamente"
    }

### Propiedad no encontrada

**HTTP `404 Not Found`**

    {
      "success": false,
      "message": "Propiedad no encontrada"
    }

### Propiedad no eliminada

**HTTP `400 Bad Request`**

    {
      "success": false,
      "message": "La propiedad no está eliminada"
    }

### Sin permisos

**HTTP `403 Forbidden`**

    {
      "success": false,
      "message": "No tienes permiso para restaurar esta propiedad"
    }

### ID inválido

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "message": "Error de validación",
      "errors": {
        "id": "El ID de la propiedad debe ser positivo"
      }
    }

### Sin autenticación

**HTTP `401 Unauthorized`**

    {
      "success": false,
      "message": "Token requerido"
    }

### Consideraciones

- El endpoint requiere un JWT válido.
- La propiedad debe haber sido eliminada previamente mediante `SoftDeletes`.
- Un usuario autenticado puede restaurar únicamente sus propias propiedades.
- La propiedad debe pertenecer al usuario identificado mediante el `sub` del JWT.
- Un usuario no puede restaurar una propiedad perteneciente a otro usuario.
- El administrador puede restaurar cualquier propiedad.
- El endpoint utiliza `withTrashed()` para localizar propiedades eliminadas.
- Si la propiedad existe pero no está eliminada, la API responde con `400 Bad Request`.
- Si la propiedad no existe, incluso entre los registros eliminados, la API responde con `404 Not Found`.
- Una vez restaurada, la propiedad vuelve a tener `deleted_at = null`.

## 7. Gestión de imágenes de propiedades

### 7.1. Obtener imágenes de propiedades

**`GET`** `/api/propiedad-imagenes`

Permite obtener las imágenes registradas en el sistema para las propiedades.

- **Autenticación requerida:** No.

#### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "items": [],
    "total": 0
  }
}
```

#### Consideraciones

* El endpoint es público y no requiere autenticación.
* Devuelve las imágenes registradas de las propiedades.
* La información de cada imagen debe estar asociada a una propiedad mediante `propiedad_id`.
* Las imágenes pertenecientes a propiedades eliminadas mediante *SoftDeletes* no deben incluirse en el listado público.
* La respuesta incluye la colección de imágenes dentro de `data.items`.
* El campo `total` indica la cantidad de imágenes devueltas.
* El endpoint permite aplicar filtros mediante parámetros de consulta.
* Los parámetros de consulta permiten realizar búsquedas o filtrados según los criterios admitidos por la API.
* La información sensible relacionada con el usuario propietario de la propiedad no debe exponerse en este endpoint.
* El endpoint no requiere conocer ni enviar el `usuario_id` para consultar las imágenes.
* Las imágenes deben incluir únicamente la información definida por el contrato para este recurso.

#### Estructura de cada imagen

La representación de cada imagen deberá contener, como mínimo, la información necesaria para identificarla y relacionarla con su propiedad.

**Ejemplo:**

```json
{
  "id": 1,
  "propiedad_id": 1,
  "url": "[https://ejemplo.com/imagenes/propiedad-1.jpg](https://ejemplo.com/imagenes/propiedad-1.jpg)",
  "es_principal": true
}
```
#### Propiedad sin imágenes

Si no existen imágenes que coincidan con la consulta, el endpoint responde igualmente con un **`200 OK`**:

```json
{
  "success": true,
  "data": {
    "items": [],
    "total": 0
  }
}
```
### 7.2. Obtener una imagen

**`GET`** `/api/propiedad-imagenes/{id}`

Permite obtener la información de una imagen específica registrada en el sistema.

- **Autenticación requerida:** No.

#### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la imagen. Debe ser un número entero positivo. |

#### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "propiedad_id": 1,
    "url": "[https://ejemplo.com/imagenes/propiedad-1.jpg](https://ejemplo.com/imagenes/propiedad-1.jpg)",
    "es_principal": true
  }
}
```

#### Response `404 Not Found` (Imagen no encontrada)

```json
{
  "success": false,
  "message": "Imagen no encontrada"
}
```

#### Response `422 Unprocessable Entity` (ID inválido)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "id": "El ID de la imagen debe ser positivo"
  }
}
```

#### Consideraciones

* El endpoint es público y no requiere autenticación.
* El ID de la imagen se obtiene desde la URL.
* El ID debe ser un número entero positivo.
* Si la imagen no existe, la API responde con `404 Not Found`.
* Una imagen perteneciente a una propiedad eliminada mediante *SoftDeletes* no debe estar disponible mediante este endpoint público.
* La imagen está asociada a una propiedad mediante `propiedad_id`.
* `es_principal` indica si la imagen es la imagen principal de la propiedad.
* La respuesta utiliza la estructura estándar definida por la API.
* No se expone información sensible del usuario propietario de la propiedad.
* La consulta de una imagen específica no requiere conocer ni enviar el `usuario_id`.

### 7.3. Crear imagen

**`POST`** `/api/propiedad-imagenes`

Permite registrar una nueva imagen asociada a una propiedad.

- **Autenticación requerida:** Sí.

#### Request

```json
{
  "propiedad_id": 1,
  "url": "[https://ejemplo.com/imagenes/propiedad-1.jpg](https://ejemplo.com/imagenes/propiedad-1.jpg)",
  "es_principal": false
}
```

#### Campos

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `propiedad_id` | integer | Sí | Identificador de la propiedad a la que pertenece la imagen. |
| `url` | string | Sí | URL o ubicación de la imagen. |
| `es_principal` | boolean | No | Indica si la imagen será establecida como principal. |

#### Response `201 Created`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "propiedad_id": 1,
    "url": "[https://ejemplo.com/imagenes/propiedad-1.jpg](https://ejemplo.com/imagenes/propiedad-1.jpg)",
    "es_principal": false
  },
  "message": "Imagen creada exitosamente"
}
```

#### Response `400 Bad Request` (JSON inválido)

```json
{
  "success": false,
  "message": "JSON inválido"
}
```

#### Response `422 Unprocessable Entity` (Datos inválidos)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "propiedad_id": "El ID de la propiedad debe ser positivo",
    "url": "La URL de la imagen es requerida"
  }
}
```

#### Response `404 Not Found` (Propiedad no encontrada)

```json
{
  "success": false,
  "message": "Propiedad no encontrada"
}
```

#### Response `401 Unauthorized` (Sin autenticación)

```json
{
  "success": false,
  "message": "Token requerido"
}
```

#### Response `403 Forbidden` (Sin permisos)

```json
{
  "success": false,
  "message": "No tienes permiso para agregar imágenes a esta propiedad"
}
```

#### Consideraciones

* El endpoint requiere un JWT válido.
* Un usuario autenticado puede agregar imágenes únicamente a sus propias propiedades.
* Un administrador puede agregar imágenes a cualquier propiedad.
* La propiedad indicada mediante `propiedad_id` debe existir.
* La propiedad no debe estar eliminada mediante *SoftDeletes*.
* El usuario propietario se determina a partir de la propiedad y no debe ser enviado por el cliente.
* No se debe aceptar un `usuario_id` en el request.
* `es_principal` es opcional.
* Una propiedad puede tener múltiples imágenes.
* Si `es_principal` es `true`, la imagen creada se establece como imagen principal de la propiedad.
* Una propiedad debe tener como máximo una imagen principal.
* Si la propiedad ya posee una imagen principal y se establece una nueva como principal, la anterior debe dejar de ser principal.
* La imagen principal también puede modificarse posteriormente mediante el endpoint correspondiente.
* La URL de la imagen debe cumplir las reglas de validación establecidas para este recurso.

### 7.4. Establecer imagen principal

**`PUT`** `/api/propiedad-imagenes/{id}/principal`

Permite establecer una imagen existente como la imagen principal de la propiedad a la que pertenece.

- **Autenticación requerida:** Sí.

#### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la imagen que se desea establecer como principal. |

#### Request

No requiere cuerpo (body). 
La imagen se identifica exclusivamente mediante el parámetro `id` de la URL.

#### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "propiedad_id": 1,
    "url": "[https://ejemplo.com/imagenes/propiedad-1.jpg](https://ejemplo.com/imagenes/propiedad-1.jpg)",
    "es_principal": true
  },
  "message": "Imagen principal establecida exitosamente"
}
```

#### Response `404 Not Found` (Imagen no encontrada)

```json
{
  "success": false,
  "message": "Imagen no encontrada"
}
```

#### Response `404 Not Found` (Propiedad no encontrada)

```json
{
  "success": false,
  "message": "Propiedad no encontrada"
}
```

#### Response `422 Unprocessable Entity` (ID inválido)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "id": "El ID de la imagen debe ser positivo"
  }
}
```

#### Response `401 Unauthorized` (Sin autenticación)

```json
{
  "success": false,
  "message": "Token requerido"
}
```

#### Response `403 Forbidden` (Sin permisos)

```json
{
  "success": false,
  "message": "No tienes permiso para modificar las imágenes de esta propiedad"
}
```

#### Consideraciones

* El endpoint requiere un JWT válido.
* El ID de la imagen se obtiene de la URL.
* El ID debe ser un número entero positivo.
* La imagen debe existir para poder establecerla como principal.
* La imagen debe pertenecer a una propiedad existente.
* La propiedad no debe estar eliminada mediante *SoftDeletes*.
* Un usuario autenticado puede establecer como principal únicamente imágenes pertenecientes a sus propias propiedades.
* Un administrador puede establecer como principal una imagen de cualquier propiedad.
* Al establecer una imagen como principal, cualquier otra imagen que actualmente sea principal para esa misma propiedad deja de serlo.
* Una propiedad puede tener como máximo una imagen principal.
* La operación no modifica la URL ni la relación de la imagen con la propiedad.
* No requiere cuerpo (body) ni parámetros adicionales.
* La respuesta devuelve la imagen actualizada.

#### Resultado esperado

Si inicialmente tenemos:

* Imagen 1 → `es_principal = true`
* Imagen 2 → `es_principal = false`
* Imagen 3 → `es_principal = false`

y ejecutamos:

```http
PUT /api/propiedad-imagenes/2/principal
```

el resultado será:

* Imagen 1 → `es_principal = false`
* Imagen 2 → `es_principal = true`
* Imagen 3 → `es_principal = false`

### 7.5. Eliminar imagen

**`DELETE`** `/api/propiedad-imagenes/{id}`

Permite eliminar una imagen asociada a una propiedad.

- **Autenticación requerida:** Sí.

#### Parámetro de ruta

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | integer | Sí | Identificador de la imagen que se desea eliminar. |

#### Response `200 OK`

```json
{
  "success": true,
  "data": [],
  "message": "Imagen eliminada exitosamente"
}
```

#### Response `404 Not Found` (Imagen no encontrada)

```json
{
  "success": false,
  "message": "Imagen no encontrada"
}
```

#### Response `422 Unprocessable Entity` (ID inválido)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "id": "El ID de la imagen debe ser positivo"
  }
}
```

#### Response `401 Unauthorized` (Sin autenticación)

```json
{
  "success": false,
  "message": "Token requerido"
}
```

#### Response `403 Forbidden` (Sin permisos)

```json
{
  "success": false,
  "message": "No tienes permiso para eliminar esta imagen"
}
```

#### Consideraciones

* El endpoint requiere un JWT válido.
* El ID de la imagen se obtiene de la URL.
* El ID debe ser un número entero positivo.
* Un usuario autenticado puede eliminar únicamente imágenes pertenecientes a sus propias propiedades.
* Un administrador puede eliminar imágenes de cualquier propiedad.
* La imagen debe existir para poder ser eliminada.
* La propiedad a la que pertenece la imagen debe existir.
* La eliminación utiliza el mecanismo definido para las imágenes de propiedades.
* Si se utiliza *SoftDeletes*, la imagen será marcada como eliminada y no se eliminará físicamente de la base de datos.
* Una imagen eliminada no debe aparecer en los listados ni en las consultas públicas habituales.
* Si la imagen eliminada era la imagen principal de la propiedad, la propiedad quedará temporalmente sin imagen principal.
* La eliminación de una imagen no debe establecer automáticamente otra imagen como principal.
* La selección de una nueva imagen principal se realiza mediante `PUT /api/propiedad-imagenes/{id}/principal`.

#### Regla sobre la imagen principal

Si la propiedad tiene:

* Imagen 1 → `es_principal = true`
* Imagen 2 → `es_principal = false`
* Imagen 3 → `es_principal = false`

y se elimina la Imagen 1, el resultado será:

* Imagen 1 → eliminada
* Imagen 2 → `es_principal = false`
* Imagen 3 → `es_principal = false`

No se seleccionará automáticamente otra imagen como principal.