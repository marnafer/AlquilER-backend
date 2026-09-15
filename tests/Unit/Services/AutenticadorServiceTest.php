<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\TokenProviderInterface;
use App\Models\RefreshToken;
use App\Models\Usuario;
use App\Repositories\RefreshTokenRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\AutenticadorService;
use App\Services\LogActividadService;
use PHPUnit\Framework\TestCase;

final class AutenticadorServiceTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function test_inicia_sesion_y_devuelve_ambos_tokens_y_rol(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
            'contrasena' => password_hash('secreto', PASSWORD_DEFAULT),
        ]);

        $usuario->id = 7;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn($usuario);

        $refreshTokenRepository
            ->expects($this->once())
            ->method('deleteByUsuarioId')
            ->with(7);

        $tokenProvider
            ->expects($this->once())
            ->method('generateAccessToken')
            ->with($usuario)
            ->willReturn('access-token-de-prueba');

        $tokenProvider
            ->expects($this->once())
            ->method('generateRefreshToken')
            ->willReturn('refresh-token-de-prueba');

        $refreshTokenRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['usuario_id'] === 7
                    && $data['token'] === 'refresh-token-de-prueba'
                    && isset($data['expires_at']);
            }));

        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(7, 'Inicio de sesión');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->login([
            'email' => ' ANA@EXAMPLE.COM ',
            'contrasena' => 'secreto',
        ]);

        $this->assertSame([
            'access_token' => 'access-token-de-prueba',
            'refresh_token' => 'refresh-token-de-prueba',
            'rol_id' => 1,
        ], $resultado);
    }

    public function test_login_funciona_para_administrador(): void
    {
        $usuario = new Usuario([
            'email' => 'admin@example.com',
            'contrasena' => password_hash('secreto', PASSWORD_DEFAULT),
        ]);

        $usuario->id = 2;
        $usuario->rol_id = 2;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('admin@example.com')
            ->willReturn($usuario);

        $refreshTokenRepository
            ->expects($this->once())
            ->method('deleteByUsuarioId')
            ->with(2);

        $tokenProvider
            ->expects($this->once())
            ->method('generateAccessToken')
            ->with($usuario)
            ->willReturn('admin-access-token');

        $tokenProvider
            ->expects($this->once())
            ->method('generateRefreshToken')
            ->willReturn('admin-refresh-token');

        $refreshTokenRepository
            ->expects($this->once())
            ->method('create');

        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(2, 'Inicio de sesión');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->login([
            'email' => 'admin@example.com',
            'contrasena' => 'secreto',
        ]);

        $this->assertSame(2, $resultado['rol_id']);
    }

    public function test_login_lanza_excepcion_si_las_credenciales_son_invalidas(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn(null);

        $tokenProvider
            ->expects($this->never())
            ->method('generateAccessToken');

        $tokenProvider
            ->expects($this->never())
            ->method('generateRefreshToken');

        $refreshTokenRepository
            ->expects($this->never())
            ->method('create');

        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Credenciales inválidas');

        $service->login([
            'email' => 'ana@example.com',
            'contrasena' => 'secreto',
        ]);
    }

    public function test_login_lanza_excepcion_si_faltan_credenciales(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->never())
            ->method('findByEmail');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->login([]);
    }

    public function test_login_lanza_excepcion_si_el_email_es_invalido(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->never())
            ->method('findByEmail');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->login([
            'email' => 'email-invalido',
            'contrasena' => 'secreto',
        ]);
    }

    public function test_login_lanza_excepcion_si_la_contrasena_no_coincide(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
            'contrasena' => password_hash(
                'secreto-correcto',
                PASSWORD_DEFAULT
            ),
        ]);

        $usuario->id = 7;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn($usuario);

        $tokenProvider
            ->expects($this->never())
            ->method('generateAccessToken');

        $tokenProvider
            ->expects($this->never())
            ->method('generateRefreshToken');

        $refreshTokenRepository
            ->expects($this->never())
            ->method('create');

        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(UnauthorizedException::class);

        $service->login([
            'email' => 'ana@example.com',
            'contrasena' => 'secreto-incorrecto',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRO
    |--------------------------------------------------------------------------
    */

    public function test_registra_un_usuario_con_rol_publico_y_contrasena_encriptada(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 7;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('ana@example.com')
            ->willReturn(false);

        $usuarioRepository
            ->expects($this->once())
            ->method('createWithRole')
            ->with(
                $this->callback(function (array $data): bool {
                    return $data['nombre'] === 'Ana'
                        && $data['apellido'] === 'Gomez'
                        && $data['email'] === 'ana@example.com'
                        && $data['telefono'] === '11 1234-5678'
                        && $data['domicilio'] === 'Av. Siempre Viva 123'
                        && isset($data['contrasena'])
                        && $data['contrasena'] !== 'secreto'
                        && password_verify(
                            'secreto',
                            $data['contrasena']
                        )
                        && !isset($data['rol_id'])
                        && !isset($data['id'])
                        && !isset($data['deleted_at']);
                }),
                1
            )
            ->willReturn($usuario);

        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(7, 'Registro de usuario');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->registrar([
            'nombre' => ' Ana ',
            'apellido' => ' Gomez ',
            'email' => ' ANA@EXAMPLE.COM ',
            'telefono' => '11 1234-5678',
            'domicilio' => ' Av. Siempre Viva 123 ',
            'contrasena' => 'secreto',
            'rol_id' => 2,
        ]);

        $this->assertSame($usuario, $resultado);
    }

    public function test_registro_ignora_intento_de_asignarse_rol_administrador(): void
    {
        $usuario = new Usuario([
            'email' => 'usuario@example.com',
        ]);

        $usuario->id = 8;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('usuario@example.com')
            ->willReturn(false);

        $usuarioRepository
            ->expects($this->once())
            ->method('createWithRole')
            ->with(
                $this->callback(function (array $data): bool {
                    return !isset($data['rol_id']);
                }),
                1
            )
            ->willReturn($usuario);

        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(8, 'Registro de usuario');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->registrar([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'email' => 'usuario@example.com',
            'telefono' => '11 1234-5678',
            'domicilio' => 'Av. Siempre Viva 123',
            'contrasena' => 'secreto',
            'rol_id' => 2,
        ]);

        $this->assertSame($usuario, $resultado);
    }

    public function test_registro_ignora_el_rol_propietario_enviado_por_el_cliente(): void
    {
        $usuario = new Usuario([
            'email' => 'dueno@example.com',
        ]);

        $usuario->id = 9;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('dueno@example.com')
            ->willReturn(false);

        $usuarioRepository
            ->expects($this->once())
            ->method('createWithRole')
            ->with($this->anything(), 1)
            ->willReturn($usuario);

        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(9, 'Registro de usuario');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->registrar([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'dueno@example.com',
            'telefono' => '11 1234-5678',
            'domicilio' => 'Av. Siempre Viva 123',
            'contrasena' => 'secreto',
            'rol' => 'propietario',
        ]);

        $this->assertSame($usuario, $resultado);
    }

    public function test_registro_ignora_un_rol_id_distinto_al_de_usuario(): void
    {
        $usuario = new Usuario([
            'email' => 'usuario@example.com',
        ]);

        $usuario->id = 10;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('usuario@example.com')
            ->willReturn(false);

        $usuarioRepository
            ->expects($this->once())
            ->method('createWithRole')
            ->with($this->anything(), 1)
            ->willReturn($usuario);

        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(10, 'Registro de usuario');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->registrar([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'email' => 'usuario@example.com',
            'telefono' => '11 1234-5678',
            'domicilio' => 'Av. Siempre Viva 123',
            'contrasena' => 'secreto',
            'rol_id' => 999,
        ]);

        $this->assertSame($usuario, $resultado);
    }

    public function test_registrar_lanza_excepcion_si_faltan_datos(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->never())
            ->method('existsByEmail');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->registrar([]);
    }

    public function test_registrar_lanza_excepcion_si_el_email_ya_esta_registrado(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $usuarioRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('ana@example.com')
            ->willReturn(true);

        $usuarioRepository
            ->expects($this->never())
            ->method('createWithRole');

        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->registrar([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
            'telefono' => '11 1234-5678',
            'domicilio' => 'Av. Siempre Viva 123',
            'contrasena' => 'secreto',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REFRESH
    |--------------------------------------------------------------------------
    */

    public function test_refresh_genera_nuevos_tokens_si_el_refresh_token_es_valido(): void
    {
        $usuario = new Usuario();

        $usuario->id = 7;
        $usuario->rol_id = 1;

        $userToken = new RefreshToken();

        $userToken->id = 15;
        $userToken->setRelation('usuario', $usuario);

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('token-viejo')
            ->willReturn($userToken);

        $refreshTokenRepository
            ->expects($this->once())
            ->method('deleteById')
            ->with(15);

        $tokenProvider
            ->expects($this->once())
            ->method('generateAccessToken')
            ->with($usuario)
            ->willReturn('nuevo-access-token');

        $tokenProvider
            ->expects($this->once())
            ->method('generateRefreshToken')
            ->willReturn('nuevo-refresh-token');

        $refreshTokenRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['usuario_id'] === 7
                    && $data['token'] === 'nuevo-refresh-token'
                    && isset($data['expires_at']);
            }));

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->refresh([
            'refresh_token' => 'token-viejo',
        ]);

        $this->assertSame([
            'access_token' => 'nuevo-access-token',
            'refresh_token' => 'nuevo-refresh-token',
            'rol_id' => 1,
        ], $resultado);
    }

    public function test_refresh_lanza_excepcion_si_falta_el_token(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $refreshTokenRepository
            ->expects($this->never())
            ->method('findValidByToken');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->refresh([]);
    }

    public function test_refresh_lanza_excepcion_si_el_token_es_invalido_o_expiro(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('token-invalido')
            ->willReturn(null);

        $refreshTokenRepository
            ->expects($this->never())
            ->method('deleteById');

        $tokenProvider
            ->expects($this->never())
            ->method('generateAccessToken');

        $tokenProvider
            ->expects($this->never())
            ->method('generateRefreshToken');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(
            'Refresh token inválido o expirado'
        );

        $service->refresh([
            'refresh_token' => 'token-invalido',
        ]);
    }

    public function test_refresh_lanza_excepcion_si_el_usuario_del_token_no_existe(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $userToken = new RefreshToken();

        $userToken->id = 15;
        $userToken->setRelation('usuario', null);

        $refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('token-sin-usuario')
            ->willReturn($userToken);

        $refreshTokenRepository
            ->expects($this->never())
            ->method('deleteById');

        $tokenProvider
            ->expects($this->never())
            ->method('generateAccessToken');

        $tokenProvider
            ->expects($this->never())
            ->method('generateRefreshToken');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Usuario no encontrado');

        $service->refresh([
            'refresh_token' => 'token-sin-usuario',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function test_logout_elimina_el_refresh_token(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $refreshTokenRepository
            ->expects($this->once())
            ->method('deleteByToken')
            ->with('token-a-eliminar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $service->logout([
            'refresh_token' => 'token-a-eliminar',
        ]);
    }

    public function test_logout_no_hace_nada_si_no_se_envia_refresh_token(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $refreshTokenRepository = $this->createMock(
            RefreshTokenRepositoryInterface::class
        );

        $tokenProvider = $this->createMock(
            TokenProviderInterface::class
        );

        $logActividadService = $this->createMock(
            LogActividadService::class
        );

        $refreshTokenRepository
            ->expects($this->never())
            ->method('deleteByToken');

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $service->logout([]);
    }
}