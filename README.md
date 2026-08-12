# sistema-alquiler — Documentación del código (estado actual)

Resumen
-------
Aplicación PHP mixta: vistas HTML para navegación por el navegador y una API REST ligera para consumo por fetch() o clientes externos. Enrutamiento centralizado, controladores separados para Web y API, validadores y sanitizadores para entrada, y soporte básico de JWT para autenticación.

Requisitos
---------
- PHP 7.4+ (o la versión que uses en el entorno)
- Composer
- MySQL (u otro DB soportado por tu configuración)
- Extensiones PHP habituales (pdo, mbstring, json, fileinfo para uploads)

Instalación rápida
------------------
1. Clona el repositorio.
2. Copia y ajusta la configuración: `config/config.php` (y si procede `src/database.php`).
3. Instala dependencias:
   - composer install
4. Regenera autoload si creas nuevas clases con namespace:
   - composer dump-autoload
5. Levanta servidor de desarrollo (opcional):
   - php -S localhost:8000 -t public

Entrada y enrutamiento
----------------------
- Punto de entrada: `public/index.php`
- Enrutamiento centralizado:
  - `src/routes/Router.php` — orquestador de rutas
  - `src/routes/web.php` — rutas de páginas HTML
  - `src/routes/api.php` — rutas de la API (JSON)

Estructura principal (resumen)
------------------------------
- public/                    — assets públicos y punto de entrada
- src/routes/                — Router.php, web.php, api.php
- src/controllers/View/      — controladores que renderizan vistas (ej. HomeController, PropiedadesController, AuthController)
- src/controllers/Api/       — controladores que devuelven JSON (ej. AutenticadorController, CategoriaController, ResenaController)
- src/Models/                — modelos Eloquent / clases de dominio (ej. Propiedad.php, Categoria.php, Consulta.php)
- src/Views/                 — layouts, páginas y componentes (header.php, menu.php, footer.php, componentes/)
- src/validators/            — validadores por entidad
- src/sanitizers/            — sanitizadores por entidad
- src/middlewares/           — middlewares (ej. AutenticadorMiddleware.php)
- src/helpers/               — utilidades (JwtHelper.php, Response.php)
- config/                    — configuración (config.php)
- src/database.php           — configuración de conexión / bootstrap DB
- scripts/, debug_test.php   — utilidades de pruebas locales

Autenticación y seguridad
-------------------------
- JWT: helpers en `src/helpers/JwtHelper.php` y middleware `src/middlewares/AutenticadorMiddleware.php`.
- Validación y sanitización centralizada: `src/validators/` y `src/sanitizers/`.
- Buenas prácticas recomendadas en proyecto:
  - Escapar salida en vistas para evitar XSS.
  - Usar consultas preparadas / ORM para prevenir inyección SQL.
  - Proteger formularios tradicionales contra CSRF si se usan.
  - Validar y restringir tipos/mimetypes y tamaño en las subidas de archivos.

Subida de archivos (imágenes)
-----------------------------
- Ruta recomendada para uploads: `public/uploads/propiedades/`
- Asegurar permisos de escritura apropiados (0755/0775 según entorno).
- El controlador de imágenes decide si acepta multipart/form-data y/o base64; revisa `src/controllers/Api/*` correspondientes.

Rutas y endpoints (ejemplos)
----------------------------
- Web (HTML): revisar `src/routes/web.php` para la lista exacta. Páginas típicas: `/`, `/propiedades`, `/propiedades/form`, `/auth/login`, `/auth/register`, `/favoritos`, `/perfil`.
- API (JSON): revisar `src/routes/api.php`. Ejemplos habituales:
  - POST /api/auth/login        → devuelve JWT
  - POST /api/auth/register
  - GET  /api/categorias
  - POST /api/resenas
  - GET  /api/propiedades

Ejemplos curl
-------------
- Login:
  curl -X POST http://localhost:8000/api/auth/login -H "Content-Type: application/json" -d '{"email":"a@b.com","password":"secret"}'

- Listar categorías:
  curl http://localhost:8000/api/categorias

- Crear reseña:
  curl -X POST http://localhost:8000/api/resenas -H "Content-Type: application/json" -d '{"propiedad_id":1,"usuario_id":2,"comentario":"Buen lugar","puntaje":5}'

Desarrollo y debugging
----------------------
- Para desarrollo activa errores en `public/index.php`:
  ini_set('display_errors', 1); error_reporting(E_ALL);
- Si hay problemas de autoload:
  composer dump-autoload -o
- Revisa `debug_test.php` para ejecuciones rápidas y `scripts/` para utilidades.

Pruebas y calidad
-----------------
- Añadir tests unitarios para: sanitizers, validators y lógica de negocio de modelos.
- Usar linters / análisis estático (PHPStan, Psalm) si es posible.
- Mantener logs centralizados y considerar un servicio de error tracking (Sentry) en producción.

Cómo cambiar rutas o añadir una API
----------------------------------
1. Edita `src/routes/web.php` o `src/routes/api.php`.
2. Crea o modifica el controlador correspondiente en `src/controllers/View/` o `src/controllers/Api/`.
3. Si introduces nuevos namespaces/clases, ejecutar:
   composer dump-autoload

Archivos de referencia rápidos
-----------------------------
- Layouts: `src/Views/layouts/{header.php,menu.php,footer.php}`
- Componentes: `src/Views/componentes/` (buscador, grid_propiedades, etc.)
- Middlewares: `src/middlewares/AutenticadorMiddleware.php`
- Helpers: `src/helpers/JwtHelper.php`, `src/helpers/Response.php`
- Configuración DB: `src/database.php`, `config/config.php`

Contribuir
----------
1. Fork del repositorio.
2. Crear branch descriptivo.
3. Tests y lint antes de PR.
4. Documenta cambios en este README cuando modifiques estructuras, rutas o contratos de API.
