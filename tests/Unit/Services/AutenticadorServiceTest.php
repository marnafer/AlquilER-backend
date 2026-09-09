<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\TokenProviderInterface;
use App\Models\Usuario;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\AutenticadorService;
use App\Services\LogActividadService;
use PHPUnit\Framework\TestCase;

final class AutenticadorServiceTest extends TestCase
{
    public function test_inicia_sesion_y_devuelve_el_token_y_rol(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
            'contrasena' => password_hash('secreto', PASSWORD_DEFAULT),
        ]);
        $usuario->id = 7;
        $usuario->rol_id = 3;

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn($usuario);
        $logActividadService
            ->expects($this->once())
            ->method('registrar')
            ->with(7, 'Inicio de sesión');
        $tokenProvider
            ->expects($this->once())
            ->method('generate')
            ->with($usuario)
            ->willReturn('token-de-prueba');

        $service = new AutenticadorService(
            $usuarioRepository,
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->login([
            'email' => ' ANA@EXAMPLE.COM ',
            'contrasena' => 'secreto',
        ]);

        $this->assertSame([
            'token' => 'token-de-prueba',
            'rol_id' => 3,
        ], $resultado);
    }

    public function test_login_lanza_excepcion_si_las_credenciales_son_invalidas(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn(null);
        $tokenProvider
            ->expects($this->never())
            ->method('generate');
        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
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
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->never())
            ->method('findByEmail');
        $tokenProvider
            ->expects($this->never())
            ->method('generate');
        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->login([]);
    }

    public function test_login_lanza_excepcion_si_el_email_es_invalido(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->never())
            ->method('findByEmail');
        $tokenProvider
            ->expects($this->never())
            ->method('generate');
        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
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
            'contrasena' => password_hash('secreto-correcto', PASSWORD_DEFAULT),
        ]);

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn($usuario);
        $tokenProvider
            ->expects($this->never())
            ->method('generate');
        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(UnauthorizedException::class);

        $service->login([
            'email' => 'ana@example.com',
            'contrasena' => 'secreto-incorrecto',
        ]);
    }

    public function test_registra_un_usuario_con_rol_publico_y_contrasena_encriptada(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
        ]);
        $usuario->id = 7;

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

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
                        && password_verify('secreto', $data['contrasena'])
                        && !isset($data['rol_id'], $data['deleted_at']);
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
            $tokenProvider,
            $logActividadService
        );

        $resultado = $service->registrar([
            'nombre' => ' ana ',
            'apellido' => ' gomez ',
            'email' => ' ANA@EXAMPLE.COM ',
            'telefono' => '11 1234-5678',
            'domicilio' => ' Av. Siempre Viva 123 ',
            'contrasena' => 'secreto',
            'rol_id' => 2,
        ]);

        $this->assertSame($usuario, $resultado);
    }

    public function test_registrar_lanza_excepcion_si_faltan_datos(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->never())
            ->method('existsByEmail');
        $usuarioRepository
            ->expects($this->never())
            ->method('createWithRole');
        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = new AutenticadorService(
            $usuarioRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->registrar([]);
    }

    public function test_registrar_lanza_excepcion_si_el_email_ya_esta_registrado(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

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
}