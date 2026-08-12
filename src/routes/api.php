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

$router->get('/api/usuarios', [UsuarioController::class, 'listarUsuariosApi']);

$router->get('/api/usuarios/{id}', [UsuarioController::class, 'mostrar']);

$router->get('/api/usuarios/me',[UsuarioController::class, 'perfil']);

$router->put('/api/usuarios/{id}', [UsuarioController::class, 'actualizar']);

$router->delete('/api/usuarios/{id}', [UsuarioController::class, 'eliminar']);

$router->post('/api/usuarios/restaurar/{id}', [UsuarioController::class, 'restaurar']);

/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$router->get('/api/categorias',[CategoriaController::class, 'listar']);

$router->post('/api/categorias',[CategoriaController::class, 'crear']);

$router->get('/api/categorias/{id}',[CategoriaController::class, 'obtener']);

$router->put('/api/categorias/{id}',[CategoriaController::class, 'actualizar']);

$router->delete('/api/categorias/{id}',[CategoriaController::class, 'eliminar']);

/*
|--------------------------------------------------------------------------
| PROVINCIAS
|--------------------------------------------------------------------------
*/

$router->get('/api/provincias', [ProvinciaController::class, 'index']);

$router->get('/api/provincias/con-localidades',[ProvinciaController::class, 'indexWithCount']);

$router->get('/api/provincias/{id}',[ProvinciaController::class, 'show']);

$router->post('/api/provincias',[ProvinciaController::class, 'store']);

$router->put('/api/provincias/{id}',[ProvinciaController::class, 'update']);

$router->delete('/api/provincias/{id}',[ProvinciaController::class, 'delete']);

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

/*
|--------------------------------------------------------------------------
| ROLES
|--------------------------------------------------------------------------
*/

$router->get('/api/roles', [RolController::class, 'index']);

$router->get('/api/roles/con-usuarios', [RolController::class, 'indexWithCount']);

$router->post('/api/roles', [RolController::class, 'store']);

$router->get('/api/roles/{id}', [RolController::class, 'show']);

$router->put('/api/roles/{id}', [RolController::class, 'update']);

$router->delete('/api/roles/{id}', [RolController::class, 'delete']);

/*	
|--------------------------------------------------------------------------
| PROPIEDAD IMAGENES
|--------------------------------------------------------------------------
*/

$router->get('/api/propiedad-imagenes/{id}', [PropiedadImagenController::class, 'mostrarApi']);

$router->get('/api/propiedad-imagenes', [PropiedadImagenController::class, 'indexApi']);

$router->post('/api/propiedad-imagenes', [PropiedadImagenController::class, 'crear']);

$router->put('/api/propiedad-imagenes/{id}/principal', [PropiedadImagenController::class, 'establecerPrincipal']);

$router->delete('/api/propiedad-imagenes/{id}', [PropiedadImagenController::class, 'eliminar']);

/*	
|--------------------------------------------------------------------------
| FAVORITOS
|--------------------------------------------------------------------------
*/

$router->get('/api/favoritos', [FavoritoController::class, 'index']);

$router->post('/api/favoritos', [FavoritoController::class, 'store']);

$router->delete('/api/favoritos', [FavoritoController::class, 'delete']);

$router->get('/api/usuarios/{id}/favoritos', [FavoritoController::class, 'indexByUsuario']);

$router->delete('/api/favoritos/{id}', [FavoritoController::class, 'deleteById']);

/*
|--------------------------------------------------------------------------
| CONSULTAS
|--------------------------------------------------------------------------
*/

$router->get('/api/consultas', [ConsultaController::class, 'index']);

$router->get('/api/consultas/{id}', [ConsultaController::class, 'show']);

$router->get('/api/consultas/propiedad/{id}', [ConsultaController::class, 'indexByPropiedad']);

$router->get('/api/consultas/usuario/{id}', [ConsultaController::class, 'indexByUsuario']);

$router->post('/api/consultas', [ConsultaController::class, 'store']);

$router->put('/api/consultas/{id}', [ConsultaController::class, 'update']);

$router->delete('/api/consultas/{id}', [ConsultaController::class, 'delete']);

/*
|--------------------------------------------------------------------------
| RESERVAS
|--------------------------------------------------------------------------
*/

$router->get('/api/reservas', [ReservaController::class, 'index']);

$router->get('/api/reservas/mis-reservas', [ReservaController::class, 'misReservas']);

$router->get('/api/reservas/{id}', [ReservaController::class, 'show']);

$router->get('/api/reservas/propiedad/{id}', [ReservaController::class, 'reservasPorPropiedad']);

$router->post('/api/reservas', [ReservaController::class, 'store']);

$router->put('/api/reservas/{id}/aprobar', [ReservaController::class, 'aprobar']);

$router->put('/api/reservas/{id}/rechazar', [ReservaController::class, 'rechazar']);

$router->put('/api/reservas/{id}/cancelar', [ReservaController::class, 'cancelar']);

$router->put('/api/reservas/{id}/finalizar', [ReservaController::class, 'finalizar']);

$router->delete('/api/reservas/{id}', [ReservaController::class, 'delete']);

/*
|--------------------------------------------------------------------------
| RESEÑAS
|--------------------------------------------------------------------------
*/

$router->get('/api/resenas', [ResenaController::class, 'index']);

$router->get('/api/resenas/estadisticas', [ResenaController::class, 'getEstadisticas']);

$router->get('/api/resenas/propiedad/{id}', [ResenaController::class, 'getByPropiedad']);

$router->get('/api/resenas/usuario/{id}', [ResenaController::class, 'getByUsuario']);

$router->get('/api/resenas/{id}', [ResenaController::class, 'show']);

$router->post('/api/resenas', [ResenaController::class, 'store']);

$router->put('/api/resenas/{id}', [ResenaController::class, 'update']);

$router->delete('/api/resenas/{id}', [ResenaController::class, 'delete']);

/*
|--------------------------------------------------------------------------
| SERVICIOS
|--------------------------------------------------------------------------
*/

$router->get('/api/servicios', [ServicioController::class, 'listar']);

$router->get('/api/servicios/{id}', [ServicioController::class, 'obtener']);

$router->post('/api/servicios', [ServicioController::class, 'crear']);

$router->put('/api/servicios/{id}', [ServicioController::class, 'actualizar']);

$router->delete('/api/servicios/{id}', [ServicioController::class, 'eliminar']);

/*
|--------------------------------------------------------------------------
| PROPIEDADES-SERVICIOS
|--------------------------------------------------------------------------
*/

$router->get('/api/propiedades-servicios', [PropiedadServicioController::class, 'index']);

$router->get('/api/propiedades-servicios/estadisticas', [PropiedadServicioController::class, 'getEstadisticas']);

$router->get('/api/propiedades-servicios/{id}', [PropiedadServicioController::class, 'show']);

$router->post('/api/propiedades-servicios', [PropiedadServicioController::class, 'store']);

$router->delete('/api/propiedades-servicios/{id}', [PropiedadServicioController::class, 'delete']);

$router->get('/api/propiedades-servicios/propiedad/{id}', [PropiedadServicioController::class, 'getByPropiedad']);

$router->get('/api/propiedades-servicios/servicio/{id}', [PropiedadServicioController::class, 'getByServicio']);

$router->post('/api/propiedades-servicios/sync/{id}', [PropiedadServicioController::class, 'sync']);

/*
|--------------------------------------------------------------------------
| LOGS ACTIVIDAD
|--------------------------------------------------------------------------
*/

$router->get('/api/logs-actividad', [LogActividadController::class, 'index']);

$router->get('/api/logs-actividad/estadisticas', [LogActividadController::class, 'getEstadisticas']);

$router->get('/api/logs-actividad/buscar', [LogActividadController::class, 'search']);

$router->get('/api/logs-actividad/fecha', [LogActividadController::class, 'getByFecha']);

$router->get('/api/logs-actividad/usuario/{id}', [LogActividadController::class, 'getByUsuario']);

$router->post('/api/logs-actividad/registrar', [LogActividadController::class, 'registrar']);

$router->get('/api/logs-actividad/{id}', [LogActividadController::class, 'show']);

$router->delete('/api/logs-actividad/{id}', [LogActividadController::class, 'delete']);

$router->delete('/api/logs-actividad/limpiar/antiguos', [LogActividadController::class, 'limpiarAntiguos']);

$router->delete('/api/logs-actividad/usuario/{id}', [LogActividadController::class, 'limpiarPorUsuario']);

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

$router->patch('/api/propiedades/{id}/restaurar', [PropiedadController::class, 'restore']);