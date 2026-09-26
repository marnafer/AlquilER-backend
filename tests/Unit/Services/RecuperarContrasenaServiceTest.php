<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use App\Models\PasswordReset;
use App\Models\Usuario;
use App\Repositories\PasswordResetRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\MailService;
use App\Services\RecuperarContrasenaService;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

final class RecuperarContrasenaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los modelos que se instancian aqui (Usuario, PasswordReset) tienen
        // casts de fecha, y al asignarlos Eloquent pide la conexion para
        // obtener el formato de fecha. Se declara una conexion propia para no
        // depender de un Capsule global dejado por otro test.
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();

        parent::tearDown();
    }

    private function crearServicio(
        UsuarioRepositoryInterface $usuarioRepository,
        PasswordResetRepositoryInterface $passwordResetRepository,
        MailService $mailService,
        LogActividadService $logService
    ): RecuperarContrasenaService {
        return new RecuperarContrasenaService(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logService,
            'http://localhost:3000'
        );
    }

    public function test_solicitar_genera_token_y_envia_el_correo(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->once())
            ->method('findByEmail')
            ->with('usuario@test.com')
            ->willReturn($usuario);

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['email'] === 'usuario@test.com'
                    && preg_match('/^[a-f0-9]{64}$/', $data['token']) === 1
                    && $data['usado'] === 0
                    && strtotime((string) $data['expiracion']) > time();
            }));

        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->once())
            ->method('enviar')
            ->with(
                'usuario@test.com',
                'Recuperación de contraseña',
                $this->callback(
                    fn (string $html): bool =>
                        str_contains($html, 'restablecer-contrasena?token=')
                        && str_contains($html, 'usuario%40test.com')
                ),
                $this->anything(),
                null
            );

        $logService = $this->createMock(LogActividadService::class);

        $logService->expects($this->never())
            ->method('registrar');

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logService
        );

        $servicio->solicitar([
            'email' => 'Usuario@Test.com',
        ]);
    }

    public function test_solicitar_no_revela_si_el_email_no_existe(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->once())
            ->method('findByEmail')
            ->with('inexistente@test.com')
            ->willReturn(null);

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->never())
            ->method('create');

        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->never())
            ->method('enviar');

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $this->createMock(LogActividadService::class)
        );

        $servicio->solicitar([
            'email' => 'inexistente@test.com',
        ]);
    }

    public function test_solicitar_lanza_validacion_si_el_email_es_invalido(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->never())
            ->method('findByEmail');

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $this->createMock(PasswordResetRepositoryInterface::class),
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(ValidationException::class);

        $servicio->solicitar([
            'email' => '',
        ]);
    }

    public function test_restablecer_actualiza_contrasena_y_marca_usado(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;

        $registro = new PasswordReset([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'expiracion' => date(
                'Y-m-d H:i:s',
                time() + 3600
            ),
            'usado' => 0,
        ]);
        $registro->id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->once())
            ->method('findByEmail')
            ->with('usuario@test.com')
            ->willReturn($usuario);

        $usuarioRepository->expects($this->once())
            ->method('update')
            ->with(
                $usuario,
                $this->callback(function (array $data): bool {
                    return password_verify(
                        'NuevaClave123',
                        $data['contrasena']
                    );
                })
            );

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->with('token-valido', 'usuario@test.com')
            ->willReturn($registro);

        $passwordResetRepository->expects($this->once())
            ->method('marcarUsado')
            ->with(1);

        $logService = $this->createMock(LogActividadService::class);

        $logService->expects($this->once())
            ->method('registrar')
            ->with(10, 'Restablecimiento de contraseña');

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $logService
        );

        $servicio->restablecer([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'contrasena' => 'NuevaClave123',
        ]);
    }

    public function test_restablecer_lanza_error_si_el_token_no_existe(): void
    {
        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->with('token-invalido', 'usuario@test.com')
            ->willReturn(null);

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->never())
            ->method('update');

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de recuperación es inválido');

        $servicio->restablecer([
            'email' => 'usuario@test.com',
            'token' => 'token-invalido',
            'contrasena' => 'NuevaClave123',
        ]);
    }

    public function test_restablecer_lanza_error_si_el_token_ya_fue_usado(): void
    {
        $registro = new PasswordReset([
            'used_token' => null,
        ]);
        $registro->id = 1;
        $registro->setAttribute('usado', 1);
        $registro->setAttribute(
            'expiracion',
            date('Y-m-d H:i:s', time() + 3600)
        );

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn($registro);

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de recuperación ya fue utilizado');

        $servicio->restablecer([
            'email' => 'usuario@test.com',
            'token' => 'token-usado',
            'contrasena' => 'NuevaClave123',
        ]);
    }

    public function test_restablecer_lanza_error_si_el_token_expiro(): void
    {
        $registro = new PasswordReset();
        $registro->id = 1;
        $registro->setAttribute('usado', 0);
        $registro->setAttribute(
            'expiracion',
            date('Y-m-d H:i:s', time() - 3600)
        );

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn($registro);

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de recuperación expiró');

        $servicio->restablecer([
            'email' => 'usuario@test.com',
            'token' => 'token-expirado',
            'contrasena' => 'NuevaClave123',
        ]);
    }
}