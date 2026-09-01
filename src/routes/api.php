<?php

use App\Controllers\Api\AutenticadorController;
use App\Controllers\Api\UsuarioController;
use App\Controllers\Api\CategoriaController;
use App\Controllers\Api\ProvinciaController;
use App\Controllers\Api\LocalidadController;
use App\Controllers\Api\RolController;
use App\Controllers\Api\PropiedadImagenController;
use App\Controllers\Api\FavoritoController;
use App\Controllers\Api\ConsultaController;
use App\Controllers\Api\ReservaController;
use App\Controllers\Api\ResenaController;
use App\Controllers\Api\ServicioController;
use App\Controllers\Api\PropiedadServicioController;
use App\Controllers\Api\LogActividadController;
use App\Controllers\Api\PropiedadController;
use App\Helpers\JwtProvider;
use App\Middlewares\AutenticadorMiddleware;
use App\Repositories\EloquentUsuarioRepository;
use App\Repositories\EloquentCategoriaRepository;
use App\Repositories\EloquentServicioRepository;
use App\Repositories\EloquentProvinciaRepository;
use App\Repositories\EloquentLocalidadRepository;
use App\Repositories\EloquentRolRepository;
use App\Repositories\EloquentPropiedadRepository;
use App\Services\AutenticadorService;
use App\Services\UsuarioService;
use App\Services\CategoriaService;
use App\Services\ServicioService;
use App\Services\ProvinciaService;
use App\Services\LocalidadService;
use App\Services\RolService;
use App\Services\PropiedadService;


// TOKEN PROVIDER

$tokenProvider = new JwtProvider();

AutenticadorMiddleware::configure($tokenProvider);

// REPOSITORIES

$usuarioRepository = new EloquentUsuarioRepository();
$categoriaRepository = new EloquentCategoriaRepository();
$servicioRepository = new EloquentServicioRepository();
$provinciaRepository = new EloquentProvinciaRepository();
$localidadRepository = new EloquentLocalidadRepository();
$rolRepository = new EloquentRolRepository();
$propiedadRepository = new EloquentPropiedadRepository();

// SERVICES

$autenticadorService = new AutenticadorService(
    $usuarioRepository,
    $tokenProvider
);

$usuarioService = new UsuarioService(
    $usuarioRepository
);

$categoriaService = new CategoriaService(
    $categoriaRepository
);

$servicioService = new ServicioService(
    $servicioRepository
);

$provinciaService = new ProvinciaService(
    $provinciaRepository
);

$localidadService = new LocalidadService(
    $localidadRepository
);

$rolService = new RolService(
    $rolRepository
);

$propiedadService = new PropiedadService(
    $propiedadRepository
);

// CONTROLLERS

$autenticadorController = new AutenticadorController(
    $autenticadorService
);

$usuarioController = new UsuarioController(
    $usuarioService
);

$categoriaController = new CategoriaController(
    $categoriaService
);

$servicioController = new ServicioController(
    $servicioService
);

$provinciaController = new ProvinciaController(
    $provinciaService
);

$localidadController = new LocalidadController(
    $localidadService
);

$rolController = new RolController(
    $rolService
);

$propiedadController = new PropiedadController(
    $propiedadService
);

/*
|--------------------------------------------------------------------------
| AUTENTICADOR
|--------------------------------------------------------------------------
*/

$router->post('/api/autenticador/login', [$autenticadorController, 'login']);

$router->post('/api/autenticador/register', [$autenticadorController, 'register']);

$router->post('/api/autenticador/logout', [$autenticadorController, 'logout']);

/*
|--------------------------------------------------------------------------
| USUARIOS
|--------------------------------------------------------------------------
*/

$router->get('/api/usuarios', [$usuarioController, 'index']);

$router->get('/api/usuarios/{id}', [$usuarioController, 'show']);

$router->get('/api/usuarios/me', [$usuarioController, 'profile']);

$router->put('/api/usuarios/{id}', [$usuarioController, 'update']);

$router->delete('/api/usuarios/{id}', [$usuarioController, 'delete']);

$router->post('/api/usuarios/{id}/restaurar', [$usuarioController, 'restore']);

/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$router->get('/api/categorias',[$categoriaController, 'index']);

$router->post('/api/categorias',[$categoriaController, 'store']);

$router->get('/api/categorias/{id}',[$categoriaController, 'show']);

$router->put('/api/categorias/{id}',[$categoriaController, 'update']);

$router->delete('/api/categorias/{id}',[$categoriaController, 'delete']);

$router->post('/api/categorias/{id}/restaurar', [$categoriaController, 'restore']);

/*
|--------------------------------------------------------------------------
| PROVINCIAS
|--------------------------------------------------------------------------
*/

$router->get('/api/provincias', [$provinciaController, 'index']);

$router->get('/api/provincias/{id}', [$provinciaController, 'show']);

$router->post('/api/provincias', [$provinciaController, 'store']);

$router->put('/api/provincias/{id}',[$provinciaController, 'update']);

$router->delete('/api/provincias/{id}',[$provinciaController, 'delete']);

$router->post('/api/provincias/{id}/restaurar', [$provinciaController, 'restore']);

/*
|--------------------------------------------------------------------------	
| LOCALIDADES
|--------------------------------------------------------------------------
*/

$router->get('/api/localidades', [$localidadController, 'index']);

$router->post('/api/localidades', [$localidadController, 'store']);

$router->get('/api/localidades/{id}', [$localidadController, 'show']);

$router->put('/api/localidades/{id}', [$localidadController, 'update']);

$router->delete('/api/localidades/{id}', [$localidadController, 'delete']);

$router->post('/api/localidades/{id}/restaurar', [$localidadController, 'restore']);

/*
|--------------------------------------------------------------------------
| ROLES
|--------------------------------------------------------------------------
*/

$router->get('/api/roles', [$rolController, 'index']);

$router->get('/api/roles/{id}', [$rolController, 'show']);

$router->post('/api/roles', [$rolController, 'store']);

$router->put('/api/roles/{id}', [$rolController, 'update']);

$router->delete('/api/roles/{id}', [$rolController, 'delete']);

$router->post('/api/roles/{id}/restaurar', [$rolController, 'restore']);

/*	
|--------------------------------------------------------------------------
| PROPIEDAD IMAGENES
|--------------------------------------------------------------------------
*/

$router->get('/api/propiedad-imagenes/{id}', [PropiedadImagenController::class, 'show']);

$router->get('/api/propiedad-imagenes', [PropiedadImagenController::class, 'index']);

$router->post('/api/propiedad-imagenes', [PropiedadImagenController::class, 'store']);

$router->put('/api/propiedad-imagenes/{id}/principal', [PropiedadImagenController::class, 'setPrincipal']);

$router->delete('/api/propiedad-imagenes/{id}', [PropiedadImagenController::class, 'delete']);

/*	
|--------------------------------------------------------------------------
| FAVORITOS
|--------------------------------------------------------------------------
*/

$router->get('/api/favoritos', [FavoritoController::class, 'index']);

$router->post('/api/favoritos', [FavoritoController::class, 'store']);

$router->get('/api/usuarios/{id}/favoritos', [FavoritoController::class, 'indexByUsuario']);

$router->delete('/api/favoritos/propiedad/{propiedad_id}', [FavoritoController::class, 'deleteByPropiedad']);

/*
|--------------------------------------------------------------------------
| CONSULTAS
|--------------------------------------------------------------------------
*/

$router->get('/api/consultas', [ConsultaController::class, 'index']);

$router->get('/api/consultas/{id}', [ConsultaController::class, 'show']);

$router->post('/api/consultas', [ConsultaController::class, 'store']);

$router->put('/api/consultas/{id}', [ConsultaController::class, 'update']);

$router->delete('/api/consultas/{id}', [ConsultaController::class, 'delete']);

$router->post('/api/consultas/{id}/restaurar', [ConsultaController::class, 'restore']);

/*
|--------------------------------------------------------------------------
| RESERVAS
|--------------------------------------------------------------------------
*/

$router->get('/api/reservas', [ReservaController::class, 'index']);

$router->get('/api/reservas/{id}', [ReservaController::class, 'show']);

$router->post('/api/reservas', [ReservaController::class, 'store']);

$router->put('/api/reservas/{id}', [ReservaController::class, 'update']);

$router->delete('/api/reservas/{id}', [ReservaController::class, 'delete']);

$router->post('/api/reservas/{id}/restaurar', [ReservaController::class, 'restore']);   

/*
|--------------------------------------------------------------------------
| RESEÑAS
|--------------------------------------------------------------------------
*/

$router->get('/api/resenas', [ResenaController::class, 'index']);

$router->get('/api/resenas/{id}', [ResenaController::class, 'show']);

$router->post('/api/resenas', [ResenaController::class, 'store']);

$router->put('/api/resenas/{id}', [ResenaController::class, 'update']);

$router->delete('/api/resenas/{id}', [ResenaController::class, 'delete']);

/*
|--------------------------------------------------------------------------
| SERVICIOS
|--------------------------------------------------------------------------
*/

$router->get('/api/servicios', [$servicioController, 'index']);

$router->get('/api/servicios/{id}', [$servicioController, 'show']);

$router->post('/api/servicios', [$servicioController, 'store']);

$router->put('/api/servicios/{id}', [$servicioController, 'update']);

$router->delete('/api/servicios/{id}', [$servicioController, 'delete']);

$router->post('/api/servicios/{id}/restaurar', [$servicioController, 'restore']);

/*
|--------------------------------------------------------------------------
| PROPIEDADES-SERVICIOS
|--------------------------------------------------------------------------
*/

$router->get('/api/propiedades/{id}/servicios', [PropiedadServicioController::class, 'index']);

$router->post('/api/propiedades/{id}/servicios', [PropiedadServicioController::class, 'store']);

$router->put('/api/propiedades/{id}/servicios', [PropiedadServicioController::class, 'update']);

$router->delete('/api/propiedades/{id}/servicios/{servicio_id}', [PropiedadServicioController::class, 'delete']);

/*
|--------------------------------------------------------------------------
| LOGS ACTIVIDAD
|--------------------------------------------------------------------------
*/

$router->get('/api/logs-actividad', [LogActividadController::class, 'index']);

$router->get('/api/logs-actividad/{id}', [LogActividadController::class, 'show']);

/*
|--------------------------------------------------------------------------
| PROPIEDADES
|--------------------------------------------------------------------------
*/

$router->get('/api/propiedades', [$propiedadController, 'index']);

$router->post('/api/propiedades', [$propiedadController, 'store']);

$router->get('/api/propiedades/{id}', [$propiedadController, 'show']);

$router->put('/api/propiedades/{id}', [$propiedadController, 'update']);

$router->delete('/api/propiedades/{id}', [$propiedadController, 'delete']);

$router->post('/api/propiedades/{id}/restaurar', [$propiedadController, 'restore']);