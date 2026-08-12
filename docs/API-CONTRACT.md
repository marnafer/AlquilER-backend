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
      "error": "Error de validación",
      "validation_errors": {
        "email": "El usuario ya existe"
      }
    }

### Datos inválidos

**HTTP `422 Unprocessable Entity`**

    {
      "success": false,
      "error": "Error de validación",
      "validation_errors": {
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

#### `PATCH /api/usuarios/{id}/restaurar`

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