<?php

declare(strict_types=1);

// CONTROLLERS
use App\Controllers\Api\AutenticadorController;
use App\Controllers\Api\UsuarioController;
use App\Controllers\Api\CategoriaController;
use App\Controllers\Api\ProvinciaController;
use App\Controllers\Api\LocalidadController;
use App\Controllers\Api\RolController;
use App\Controllers\Api\PropiedadImagenController;
use App\Controllers\Api\ConsultaController;
use App\Controllers\Api\ReservaController;
use App\Controllers\Api\ResenaController;
use App\Controllers\Api\ServicioController;
use App\Controllers\Api\PropiedadServicioController;
use App\Controllers\Api\LogActividadController;
use App\Controllers\Api\PropiedadController;
use App\Controllers\Api\FavoritoController;
use App\Controllers\Api\MensajeConsultaController;

// HELPERS - MIDDLEWARES
use App\Helpers\JwtProvider;
use App\Middlewares\AutenticadorMiddleware;

// REPOSITORIES
use App\Repositories\EloquentUsuarioRepository;
use App\Repositories\EloquentCategoriaRepository;
use App\Repositories\EloquentServicioRepository;
use App\Repositories\EloquentProvinciaRepository;
use App\Repositories\EloquentLocalidadRepository;
use App\Repositories\EloquentRolRepository;
use App\Repositories\EloquentPropiedadRepository;
use App\Repositories\EloquentPropiedadImagenRepository;
use App\Repositories\EloquentLogActividadRepository;
use App\Repositories\EloquentFavoritoRepository;
use App\Repositories\EloquentReservaRepository;
use App\Repositories\EloquentConsultaRepository;
use App\Repositories\EloquentResenaRepository;
use App\Repositories\EloquentPropiedadServicioRepository;
use App\Repositories\EloquentRefreshTokenRepository;
use App\Repositories\EloquentMensajeConsultaRepository;

// SERVICES
use App\Services\AutenticadorService;
use App\Services\UsuarioService;
use App\Services\CategoriaService;
use App\Services\ServicioService;
use App\Services\ProvinciaService;
use App\Services\LocalidadService;
use App\Services\RolService;
use App\Services\PropiedadService;
use App\Services\PropiedadImagenService;
use App\Services\LogActividadService;
use App\Services\FavoritoService;
use App\Services\ReservaService;
use App\Services\ConsultaService;
use App\Services\ResenaService;
use App\Services\PropiedadServicioService;
use App\Services\GestorArchivosLocales;
use App\Services\MensajeConsultaService;

//VALIDATORS
use App\Validators\CargaImagenValidator;

// POLICIES
use App\Policies\ConsultaPolicy;
use App\Policies\FavoritoPolicy;
use App\Policies\PropiedadImagenPolicy;
use App\Policies\PropiedadPolicy;
use App\Policies\ResenaPolicy;
use App\Policies\ReservaPolicy;

// TOKEN PROVIDER
$tokenProvider = new JwtProvider();
AutenticadorMiddleware::configure($tokenProvider);

// INSTANCIAR REPOSITORIES
$usuarioRepository = new EloquentUsuarioRepository();
$categoriaRepository = new EloquentCategoriaRepository();
$servicioRepository = new EloquentServicioRepository();
$provinciaRepository = new EloquentProvinciaRepository();
$localidadRepository = new EloquentLocalidadRepository();
$rolRepository = new EloquentRolRepository();
$propiedadRepository = new EloquentPropiedadRepository();
$propiedadImagenRepository = new EloquentPropiedadImagenRepository();
$logActividadRepository = new EloquentLogActividadRepository();
$favoritoRepository = new EloquentFavoritoRepository();
$reservaRepository = new EloquentReservaRepository();
$consultaRepository = new EloquentConsultaRepository();
$resenaRepository = new EloquentResenaRepository();
$propiedadServicioRepository = new EloquentPropiedadServicioRepository();
$refreshTokenRepository = new EloquentRefreshTokenRepository();
$mensajeConsultaRepository = new EloquentMensajeConsultaRepository();

//INSTANCIAR POLICIES
$consultaPolicy = new ConsultaPolicy();
$favoritoPolicy = new FavoritoPolicy(); 
$propiedadPolicy = new PropiedadPolicy();
$propiedadImagenPolicy = new PropiedadImagenPolicy();
$resenaPolicy = new ResenaPolicy();
$reservaPolicy = new ReservaPolicy();

// INSTANCIAR SERVICES
$logActividadService = new LogActividadService(
    $logActividadRepository
);

$autenticadorService = new AutenticadorService(
    $usuarioRepository,
    $refreshTokenRepository,
    $tokenProvider,
    $logActividadService
);

$usuarioService = new UsuarioService(
    $usuarioRepository,
    $logActividadService
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
    $localidadRepository,
    $provinciaRepository
);

$rolService = new RolService(
    $rolRepository
);

$propiedadService = new PropiedadService(
    $propiedadRepository,
    $logActividadService,
    $categoriaRepository,
    $localidadRepository,
    $propiedadPolicy
);

$gestorArchivos = new GestorArchivosLocales();
$cargaImagenValidator = new CargaImagenValidator();

$propiedadImagenService = new PropiedadImagenService(
    $propiedadImagenRepository,
    $propiedadService,
    $logActividadService,
    $gestorArchivos,
    $cargaImagenValidator,
    $propiedadImagenPolicy
);

$favoritoService = new FavoritoService(
    $favoritoRepository,
    $propiedadRepository,
    $logActividadService,
    $favoritoPolicy 
);

$reservaService = new ReservaService(
    $reservaRepository,
    $propiedadRepository,
    $reservaPolicy,
    $logActividadService
);

$consultaService = new ConsultaService(
    $consultaRepository,
    $propiedadRepository,
    $usuarioRepository,
    $mensajeConsultaRepository,
    $logActividadService,
    $consultaPolicy
);

$resenaService = new ResenaService(
    $resenaRepository,
    $reservaRepository,
    $resenaPolicy,
    $logActividadService
);

$propiedadServicioService = new PropiedadServicioService(
    $propiedadServicioRepository,
    $propiedadRepository,
    $servicioRepository,
    $logActividadService,
    $propiedadPolicy
);

$mensajeConsultaService = new MensajeConsultaService(
    $mensajeConsultaRepository,
    $consultaService,
    $logActividadService
);

// CONTROLLERS & EXPORT
return [
    'autenticadorController' => new AutenticadorController($autenticadorService),
    'usuarioController' => new UsuarioController($usuarioService),
    'categoriaController' => new CategoriaController($categoriaService),
    'servicioController' => new ServicioController($servicioService),
    'provinciaController' => new ProvinciaController($provinciaService),
    'localidadController' => new LocalidadController($localidadService),
    'rolController' => new RolController($rolService),
    'propiedadController' => new PropiedadController($propiedadService),
    'propiedadImagenController' => new PropiedadImagenController($propiedadImagenService),
    'logActividadController' => new LogActividadController($logActividadService),
    'favoritoController' => new FavoritoController($favoritoService),
    'reservaController' => new ReservaController($reservaService),
    'consultaController' => new ConsultaController($consultaService),
    'resenaController' => new ResenaController($resenaService),
    'mensajeConsultaController' => new MensajeConsultaController($mensajeConsultaService),
    'propiedadServicioController' => new PropiedadServicioController($propiedadServicioService),
];