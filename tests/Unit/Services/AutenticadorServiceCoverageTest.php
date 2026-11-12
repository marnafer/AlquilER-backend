<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\TokenProviderInterface;
use App\Models\Usuario;
use App\Repositories\RefreshTokenRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\AutenticadorService;
use App\Services\LogActividadService;
use App\Services\MailService;
use PHPUnit\Framework\TestCase;

final class AutenticadorServiceCoverageTest extends TestCase
{
    public function test_login_lanza_excepcion_si_el_email_no_esta_verificado(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
            'contrasena' => password_hash('secreto', PASSWORD_DEFAULT),
        ]);
        $usuario->id = 7;
        $usuario->rol_id = 1;
        $usuario->email_verified_at = null;

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('ana@example.com')
            ->willReturn($usuario);

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Email no verificado');

        $service->login([
            'email' => 'ana@example.com',
            'contrasena' => 'secreto',
        ]);
    }

    public function test_registrar_envia_correo_de_verificacion(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->id = 7;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);
        $mailService = $this->createMock(MailService::class);

        $usuarioRepository->expects($this->once())->method('existsByEmail')->with('ana@example.com')->willReturn(false);
        $usuarioRepository->expects($this->once())->method('createWithRole')->willReturn($usuario);
        $usuarioRepository->expects($this->once())->method('update');
        $logActividadService->expects($this->once())->method('registrar');
        $mailService->expects($this->once())->method('enviar')->willReturn(true);

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService,
            $mailService,
            'http://localhost:3000'
        );

        $resultado = $service->registrar([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
            'telefono' => '11 1234-5678',
            'domicilio' => 'Av. Siempre Viva 123',
            'contrasena' => 'secreto',
        ]);

        $this->assertSame($usuario, $resultado);
    }

    public function test_registrar_lanza_excepcion_si_no_puede_enviar_correo(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->id = 7;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);
        $mailService = $this->createMock(MailService::class);

        $usuarioRepository->expects($this->once())->method('existsByEmail')->with('ana@example.com')->willReturn(false);
        $usuarioRepository->expects($this->once())->method('createWithRole')->willReturn($usuario);
        $usuarioRepository->expects($this->once())->method('update');
        $logActividadService->expects($this->once())->method('registrar');
        $mailService->expects($this->once())->method('enviar')->willReturn(false);

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService,
            $mailService,
            'http://localhost:3000'
        );

        $this->expectException(\RuntimeException::class);

        $service->registrar([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
            'telefono' => '11 1234-5678',
            'domicilio' => 'Av. Siempre Viva 123',
            'contrasena' => 'secreto',
        ]);
    }

    public function test_registrar_lanza_excepcion_si_no_hay_servicio_de_correo_en_produccion(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->id = 7;
        $usuario->rol_id = 1;

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $usuarioRepository->expects($this->once())->method('existsByEmail')->with('ana@example.com')->willReturn(false);
        $usuarioRepository->expects($this->once())->method('createWithRole')->willReturn($usuario);
        $usuarioRepository->expects($this->once())->method('update');
        $logActividadService->expects($this->once())->method('registrar');

        $previousEnv = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'production';

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService,
            null,
            'http://localhost:3000'
        );

        $this->expectException(\RuntimeException::class);

        try {
            $service->registrar([
                'nombre' => 'Ana',
                'apellido' => 'Gomez',
                'email' => 'ana@example.com',
                'telefono' => '11 1234-5678',
                'domicilio' => 'Av. Siempre Viva 123',
                'contrasena' => 'secreto',
            ]);
        } finally {
            if ($previousEnv === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }

    public function test_verificar_email_lanza_excepcion_si_faltan_datos(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $logActividadService = $this->createMock(LogActividadService::class);

        $service = new AutenticadorService(
            $usuarioRepository,
            $refreshTokenRepository,
            $tokenProvider,
            $logActividadService
        );

        $this->expectException(ValidationException::class);

        $service->verificarEmail([]);
    }

    public function test_verificar_email_lanza_excepcion_si_el_enlace_es_invalido(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository->expects($this->once())->method('findByEmail')->with('ana@example.com')->willReturn(null);

        $service = new AutenticadorService(
            $usuarioRepository,
            $this->createMock(RefreshTokenRepositoryInterface::class),
            $this->createMock(TokenProviderInterface::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);

        $service->verificarEmail([
            'email' => 'ana@example.com',
            'token' => 'token',
        ]);
    }

    public function test_verificar_email_lanza_excepcion_si_el_enlace_expiro(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->email_verification_token = hash('sha256', 'token');
        $usuario->email_verification_expires_at = date('Y-m-d H:i:s', strtotime('-1 minute'));

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository->expects($this->once())->method('findByEmail')->with('ana@example.com')->willReturn($usuario);

        $service = new AutenticadorService(
            $usuarioRepository,
            $this->createMock(RefreshTokenRepositoryInterface::class),
            $this->createMock(TokenProviderInterface::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);

        $service->verificarEmail([
            'email' => 'ana@example.com',
            'token' => 'token',
        ]);
    }

    public function test_verificar_email_marca_el_correo_como_verificado(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->email_verification_token = hash('sha256', 'token');
        $usuario->email_verification_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository->expects($this->once())->method('findByEmail')->with('ana@example.com')->willReturn($usuario);
        $usuarioRepository->expects($this->once())->method('update');

        $service = new AutenticadorService(
            $usuarioRepository,
            $this->createMock(RefreshTokenRepositoryInterface::class),
            $this->createMock(TokenProviderInterface::class),
            $this->createMock(LogActividadService::class)
        );

        $service->verificarEmail([
            'email' => 'ana@example.com',
            'token' => 'token',
        ]);

        $this->assertTrue(true);
    }
}