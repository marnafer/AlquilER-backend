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