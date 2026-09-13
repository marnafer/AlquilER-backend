<?php

// 1. Cargar el bootstrap y recibir los controladores ya instanciados
$controllers = require __DIR__ . '/../bootstrap.php';

// 2. Extraer las variables de los controladores para usarlas limpiamente en las rutas
extract($controllers);

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

$router->get('/api/propiedad-imagenes/{id}', [$propiedadImagenController, 'show']);

$router->get('/api/propiedad-imagenes', [$propiedadImagenController, 'index']);

$router->post('/api/propiedad-imagenes', [$propiedadImagenController, 'store']);

$router->put('/api/propiedad-imagenes/{id}/principal', [$propiedadImagenController, 'setPrincipal']);

$router->delete('/api/propiedad-imagenes/{id}', [$propiedadImagenController, 'delete']);

/*	
|--------------------------------------------------------------------------
| FAVORITOS
|--------------------------------------------------------------------------
*/

$router->get('/api/favoritos', [$favoritoController, 'index']);

$router->post('/api/favoritos', [$favoritoController, 'store']);

$router->get('/api/usuarios/{id}/favoritos', [$favoritoController, 'indexByUsuario']);

$router->delete('/api/favoritos/propiedad/{propiedad_id}', [$favoritoController, 'deleteByPropiedad']);

/*
|--------------------------------------------------------------------------
| RESERVAS
|--------------------------------------------------------------------------
*/

$router->get('/api/reservas', [$reservaController, 'index']);

$router->get('/api/reservas/{id}', [$reservaController, 'show']);

$router->post('/api/reservas', [$reservaController, 'store']);

$router->put('/api/reservas/{id}', [$reservaController, 'update']);

$router->delete('/api/reservas/{id}', [$reservaController, 'delete']);

$router->post('/api/reservas/{id}/restaurar', [$reservaController, 'restore']);

$router->get('/api/reservas/usuario/{usuarioId}', [$reservaController, 'getByUsuario']);

$router->get('/api/reservas/propiedad/{propiedadId}', [$reservaController, 'getByPropiedad']);

$router->patch('/api/reservas/{id}/estado', [$reservaController, 'cambiarEstado']);

$router->get('/api/reservas/verificar-disponibilidad', [$reservaController, 'verificarDisponibilidad']);

/*
|--------------------------------------------------------------------------
| CONSULTAS
|--------------------------------------------------------------------------
*/

$router->get('/api/consultas', [$consultaController, 'index']);

$router->get('/api/consultas/{id}', [$consultaController, 'show']);

$router->get('/api/consultas/propiedad/{propiedadId}', [$consultaController, 'indexByPropiedad']);

$router->get('/api/consultas/usuario/{usuarioId}', [$consultaController, 'indexByUsuario']);

$router->post('/api/consultas', [$consultaController, 'store']);

$router->put('/api/consultas/{id}', [$consultaController, 'update']);

$router->delete('/api/consultas/{id}', [$consultaController, 'delete']);

/*
|--------------------------------------------------------------------------
| RESEÑAS
|--------------------------------------------------------------------------
*/

$router->get('/api/resenas', [$resenaController, 'index']);

$router->get('/api/resenas/{id}', [$resenaController, 'show']);

$router->get('/api/resenas/reserva/{reservaId}', [$resenaController, 'getByReserva']);

$router->get('/api/resenas/propiedad/{propiedadId}', [$resenaController, 'getByPropiedad']);

$router->get('/api/resenas/usuario/{usuarioId}', [$resenaController, 'getByUsuario']);

$router->get('/api/resenas/calificador/{calificadorId}', [$resenaController, 'getByCalificador']);

$router->post('/api/resenas', [$resenaController, 'store']);

$router->put('/api/resenas/{id}', [$resenaController, 'update']);

$router->delete('/api/resenas/{id}', [$resenaController, 'delete']);

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

$router->get('/api/propiedades/{propiedadId}/servicios', [$propiedadServicioController, 'index']);

$router->get('/api/servicios/{servicioId}/propiedades', [$propiedadServicioController, 'getPropiedadesByServicio']);

$router->post('/api/propiedades/{propiedadId}/servicios', [$propiedadServicioController, 'store']);

$router->post('/api/propiedades/{propiedadId}/servicios/multiple', [$propiedadServicioController, 'storeMultiple']);

$router->put('/api/propiedades/{propiedadId}/servicios', [$propiedadServicioController, 'update']);

$router->delete('/api/propiedades/{propiedadId}/servicios/{servicioId}', [$propiedadServicioController, 'delete']);

/*
|--------------------------------------------------------------------------
| LOGS ACTIVIDAD
|--------------------------------------------------------------------------
*/

$router->get('/api/logs-actividad', [$logActividadController, 'index']);

$router->get('/api/logs-actividad/{id}', [$logActividadController, 'show']);

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