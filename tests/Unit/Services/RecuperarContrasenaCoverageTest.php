<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\RuntimeException;
use App\Models\Usuario;
use App\Repositories\PasswordResetRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\MailService;
use App\Services\RecuperarContrasenaService;
use PHPUnit\Framework\TestCase;

final class RecuperarContrasenaCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        \Illuminate\Database\Eloquent\Model::unsetConnectionResolver();
        parent::tearDown();
    }

    public function test_solicitar_lanza_excepcion_si_no_envia_correo_en_produccion(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('usuario@test.com')
            ->willReturn($usuario);

        $passwordResetRepository = $this->createMock(PasswordResetRepositoryInterface::class);
        $passwordResetRepository
            ->expects($this->once())
            ->method('create');

        $mailService = $this->createMock(MailService::class);
        $mailService
            ->expects($this->once())
            ->method('enviar')
            ->willReturn(false);

        $logActividadService = $this->createMock(LogActividadService::class);

        $previousEnv = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'production';

        $service = new RecuperarContrasenaService(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logActividadService,
            'http://localhost:3000'
        );

        $this->expectException(\RuntimeException::class);

        try {
            $service->solicitar(['email' => 'usuario@test.com']);
        } finally {
            if ($previousEnv === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }

    public function test_restablecer_lanza_excepcion_si_no_envia_correo_en_produccion(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';

        $registro = new \App\Models\PasswordReset();
        $registro->id = 5;
        $registro->usado = 0;
        $registro->expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $registro->email = 'usuario@test.com';

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository->expects($this->once())->method('findByEmail')->with('usuario@test.com')->willReturn($usuario);
        $usuarioRepository->expects($this->once())->method('update');

        $passwordResetRepository = $this->createMock(PasswordResetRepositoryInterface::class);
        $passwordResetRepository->expects($this->once())->method('findByTokenAndEmail')->willReturn($registro);
        $passwordResetRepository->expects($this->once())->method('marcarUsado');

        $mailService = $this->createMock(MailService::class);
        $mailService->expects($this->once())->method('enviar')->willReturn(false);

        $logActividadService = $this->createMock(LogActividadService::class);
        $logActividadService->expects($this->once())->method('registrar');

        $previousEnv = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'production';

        $service = new RecuperarContrasenaService(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logActividadService,
            'http://localhost:3000'
        );

        $this->expectException(\RuntimeException::class);

        try {
            $service->restablecer([
                'email' => 'usuario@test.com',
                'token' => 'token',
                'contrasena' => 'NuevaPass123',
            ]);
        } finally {
            if ($previousEnv === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }

    public function test_solicitar_cambio_desde_perfil_lanza_excepcion_si_no_envia_correo_en_produccion(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';
        $usuario->contrasena = password_hash('actual', PASSWORD_DEFAULT);

        $passwordResetRepository = $this->createMock(PasswordResetRepositoryInterface::class);
        $passwordResetRepository->expects($this->once())->method('create');

        $mailService = $this->createMock(MailService::class);
        $mailService->expects($this->once())->method('enviar')->willReturn(false);

        $previousEnv = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'production';

        $service = new RecuperarContrasenaService(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $mailService,
            $this->createMock(LogActividadService::class),
            'http://localhost:3000'
        );

        $this->expectException(\RuntimeException::class);

        try {
            $service->solicitarCambioDesdePerfil($usuario, 'actual', 'NuevaPass123');
        } finally {
            if ($previousEnv === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }

    public function test_confirmar_cambio_lanza_excepcion_si_no_envia_correo_en_produccion(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';

        $registro = new \App\Models\PasswordReset();
        $registro->id = 5;
        $registro->usado = 0;
        $registro->expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $registro->contrasena_temporal = password_hash('NuevaPass123', PASSWORD_DEFAULT);

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository->expects($this->once())->method('findByEmail')->with('usuario@test.com')->willReturn($usuario);
        $usuarioRepository->expects($this->once())->method('update');

        $passwordResetRepository = $this->createMock(PasswordResetRepositoryInterface::class);
        $passwordResetRepository->expects($this->once())->method('findByTokenAndEmail')->willReturn($registro);
        $passwordResetRepository->expects($this->once())->method('marcarUsado');

        $mailService = $this->createMock(MailService::class);
        $mailService->expects($this->once())->method('enviar')->willReturn(false);

        $logActividadService = $this->createMock(LogActividadService::class);
        $logActividadService->expects($this->once())->method('registrar');

        $previousEnv = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'production';

        $service = new RecuperarContrasenaService(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logActividadService,
            'http://localhost:3000'
        );

        $this->expectException(\RuntimeException::class);

        try {
            $service->confirmarCambio('usuario@test.com', 'token');
        } finally {
            if ($previousEnv === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }

}
