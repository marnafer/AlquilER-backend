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

/*
|--------------------------------------------------------------------------
| AUTENTICADOR
|--------------------------------------------------------------------------
*/

$router->post('/api/autenticador/login', [AutenticadorController::class, 'login']);

$router->post('/api/autenticador/register', [AutenticadorController::class, 'register']);

$router->post('/api/autenticador/logout', [AutenticadorController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| USUARIOS
|--------------------------------------------------------------------------
*/

$router->get('/api/usuarios', [UsuarioController::class, 'index']);

$router->get('/api/usuarios/{id}', [UsuarioController::class, 'show']);

$router->get('/api/usuarios/me',[UsuarioController::class, 'profile']);

$router->put('/api/usuarios/{id}', [UsuarioController::class, 'update']);

$router->delete('/api/usuarios/{id}', [UsuarioController::class, 'delete']);

$router->post('/api/usuarios/{id}/restaurar', [UsuarioController::class, 'restore']);

/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$router->get('/api/categorias',[CategoriaController::class, 'index']);

$router->post('/api/categorias',[CategoriaController::class, 'store']);

$router->get('/api/categorias/{id}',[CategoriaController::class, 'show']);

$router->put('/api/categorias/{id}',[CategoriaController::class, 'update']);

$router->delete('/api/categorias/{id}',[CategoriaController::class, 'delete']);

$router->post('/api/categorias/{id}/restaurar', [CategoriaController::class, 'restore']);

/*
|--------------------------------------------------------------------------
| PROVINCIAS
|--------------------------------------------------------------------------
*/

$router->get('/api/provincias', [ProvinciaController::class, 'index']);

$router->get('/api/provincias/{id}',[ProvinciaController::class, 'show']);

$router->post('/api/provincias',[ProvinciaController::class, 'store']);

$router->put('/api/provincias/{id}',[ProvinciaController::class, 'update']);

$router->delete('/api/provincias/{id}',[ProvinciaController::class, 'delete']);

$router->post('/api/provincias/{id}/restaurar', [ProvinciaController::class, 'restore']);

/*
|--------------------------------------------------------------------------	
| LOCALIDADES
|--------------------------------------------------------------------------
*/

$router->get('/api/localidades', [LocalidadController::class, 'index']);

$router->post('/api/localidades', [LocalidadController::class, 'store']);

$router->get('/api/localidades/{id}', [LocalidadController::class, 'show']);

$router->put('/api/localidades/{id}', [LocalidadController::class, 'update']);

$router->delete('/api/localidades/{id}', [LocalidadController::class, 'delete']);

$router->post('/api/localidades/{id}/restaurar', [LocalidadController::class, 'restore']);

/*
|--------------------------------------------------------------------------
| ROLES
|--------------------------------------------------------------------------
*/

$router->get('/api/roles', [RolController::class, 'index']);

$router->get('/api/roles/{id}', [RolController::class, 'show']);

$router->post('/api/roles', [RolController::class, 'store']);

$router->put('/api/roles/{id}', [RolController::class, 'update']);

$router->delete('/api/roles/{id}', [RolController::class, 'delete']);

$router->post('/api/roles/{id}/restaurar', [RolController::class, 'restore']);

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

$router->get('/api/servicios', [ServicioController::class, 'index']);

$router->get('/api/servicios/{id}', [ServicioController::class, 'show']);

$router->post('/api/servicios', [ServicioController::class, 'store']);

$router->put('/api/servicios/{id}', [ServicioController::class, 'update']);

$router->delete('/api/servicios/{id}', [ServicioController::class, 'delete']);

$router->post('/api/servicios/{id}/restaurar', [ServicioController::class, 'restore']);

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

$router->get('/api/propiedades', [PropiedadController::class, 'index']);

$router->post('/api/propiedades', [PropiedadController::class, 'store']);

$router->get('/api/propiedades/{id}', [PropiedadController::class, 'show']);

$router->put('/api/propiedades/{id}', [PropiedadController::class, 'update']);

$router->delete('/api/propiedades/{id}', [PropiedadController::class, 'delete']);

$router->post('/api/propiedades/{id}/restaurar', [PropiedadController::class, 'restore']);