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
        $usuario->email = 'usuario@test.com';

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $usuarioRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('usuario@test.com')
            ->willReturn($usuario);

        $tokenOriginal = null;
        $tokenHashGuardado = null;

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data) use (&$tokenHashGuardado): bool {
                    $tokenHashGuardado = $data['token'];

                    return $data['email'] === 'usuario@test.com'
                        && preg_match('/^[a-f0-9]{64}$/', $data['token']) === 1
                        && $data['usado'] === 0
                        && strtotime((string) $data['expiracion']) > time();
                }
            ));

        $mailService = $this->createMock(MailService::class);

        $mailService
            ->expects($this->exactly(2))
            ->method('enviar')
            ->willReturnCallback(
                function (
                    string $to,
                    string $subject,
                    string $html
                ) use (&$tokenOriginal): bool {
                    if ($subject === 'Recuperación de contraseña') {
                        if (
                            preg_match(
                                '/[?&]token=([^&"]+)/',
                                $html,
                                $matches
                            ) !== 1
                        ) {
                            return false;
                        }

                        $tokenOriginal = urldecode($matches[1]);
                    }

                    return $to === 'usuario@test.com';
                }
            );

        $logActividadService = $this->createMock(LogActividadService::class);
        $logActividadService
            ->expects($this->never())
            ->method('registrar');

        $service = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logActividadService
        );

        $service->solicitar([
            'email' => 'Usuario@Test.com',
        ]);

        $this->assertNotNull($tokenOriginal);
        $this->assertNotNull($tokenHashGuardado);

        $this->assertNotSame(
            $tokenOriginal,
            $tokenHashGuardado
        );

        $this->assertSame(
            hash('sha256', $tokenOriginal),
            $tokenHashGuardado
        );
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
        $usuario->email = 'usuario@test.com';

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

        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->once())
            ->method('enviar')
            ->with(
                'usuario@test.com',
                'Tu contraseña fue restablecida',
                $this->anything(),
                $this->anything()
            );

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
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

    public function test_restablecer_lanza_validacion_si_los_datos_son_invalidos(): void
    {
        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $this->createMock(PasswordResetRepositoryInterface::class),
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(ValidationException::class);

        $servicio->restablecer([
            'email' => '',
            'token' => '',
            'contrasena' => '',
        ]);
    }

    public function test_restablecer_lanza_error_si_el_usuario_no_existe(): void
    {
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
            ->willReturn(null);

        $usuarioRepository->expects($this->never())
            ->method('update');

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->with('token-valido', 'usuario@test.com')
            ->willReturn($registro);

        $passwordResetRepository->expects($this->never())
            ->method('marcarUsado');

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
            'token' => 'token-valido',
            'contrasena' => 'NuevaClave123',
        ]);
    }

    public function test_solicitar_cambio_desde_perfil_guarda_hash_y_envia_correo(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';
        $usuario->contrasena = password_hash('actual', PASSWORD_DEFAULT);

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return $data['email'] === 'usuario@test.com'
                        && preg_match('/^[a-f0-9]{64}$/', $data['token']) === 1
                        && $data['usado'] === 0
                        && isset($data['contrasena_temporal'])
                        && password_verify(
                            'nueva123',
                            $data['contrasena_temporal']
                        );
                }
            ));

        $mailService = $this->createMock(MailService::class);

        $mailService
            ->expects($this->once())
            ->method('enviar')
            ->with(
                'usuario@test.com',
                'Confirmá el cambio de contraseña',
                $this->callback(function (string $html): bool {
                    return str_contains($html, 'confirmar=1');
                }),
                $this->anything()
            );

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $this->createMock(LogActividadService::class)
        );

        $servicio->solicitarCambioDesdePerfil(
            $usuario,
            'actual',
            'nueva123'
        );
    }

    public function test_solicitar_cambio_desde_perfil_falla_si_actual_es_incorrecta(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';
        $usuario->contrasena = password_hash('actual', PASSWORD_DEFAULT);

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository
            ->expects($this->never())
            ->method('create');

        $mailService = $this->createMock(MailService::class);

        $mailService
            ->expects($this->never())
            ->method('enviar');

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $mailService,
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(ValidationException::class);

        $servicio->solicitarCambioDesdePerfil(
            $usuario,
            'incorrecta',
            'nueva123'
        );
    }

    public function test_confirmar_cambio_aplica_la_contrasena_guardada_y_marca_usado(): void
    {
        $hashNuevo = password_hash('nueva123', PASSWORD_DEFAULT);

        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';

        $registro = new PasswordReset([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'expiracion' => date('Y-m-d H:i:s', time() + 3600),
            'usado' => 0,
            'contrasena_temporal' => $hashNuevo,
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
                $this->callback(function (array $data) use ($hashNuevo): bool {
                    return $data['contrasena'] === $hashNuevo;
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
            ->with(10, 'Confirmación de cambio de contraseña');

        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->once())
            ->method('enviar')
            ->with(
                'usuario@test.com',
                'Tu contraseña fue actualizada',
                $this->anything(),
                $this->anything()
            );

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $mailService,
            $logService
        );

        $servicio->confirmarCambio('usuario@test.com', 'token-valido');
    }

    public function test_confirmar_cambio_falla_si_ya_fue_usado(): void
    {
        $registro = new PasswordReset([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'expiracion' => date('Y-m-d H:i:s', time() + 3600),
            'usado' => 1,
            'contrasena_temporal' => password_hash('nueva123', PASSWORD_DEFAULT),
        ]);
        $registro->id = 1;

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn($registro);

        $passwordResetRepository->expects($this->never())
            ->method('marcarUsado');

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Este cambio de contraseña ya fue confirmado');

        $servicio->confirmarCambio('usuario@test.com', 'token-valido');
    }

    public function test_confirmar_cambio_falla_si_token_no_existe(): void
    {
        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn(null);

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de confirmación es inválido');

        $servicio->confirmarCambio('usuario@test.com', 'token-invalido');
    }

    public function test_solicitar_cambio_desde_perfil_falla_si_la_nueva_es_invalida(): void
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->email = 'usuario@test.com';
        $usuario->contrasena = password_hash('actual123', PASSWORD_DEFAULT);

        $usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $this->createMock(PasswordResetRepositoryInterface::class),
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(ValidationException::class);

        $servicio->solicitarCambioDesdePerfil($usuario, 'actual123', 'abc');
    }

    public function test_confirmar_cambio_falla_si_el_token_expiro(): void
    {
        $registro = new PasswordReset([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'expiracion' => date('Y-m-d H:i:s', time() - 3600),
            'usado' => 0,
            'contrasena_temporal' => password_hash('nueva123', PASSWORD_DEFAULT),
        ]);
        $registro->id = 1;

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn($registro);

        $passwordResetRepository->expects($this->never())
            ->method('marcarUsado');

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de confirmación expiró');

        $servicio->confirmarCambio('usuario@test.com', 'token-valido');
    }

    public function test_confirmar_cambio_falla_si_falta_la_contrasena_temporal(): void
    {
        $registro = new PasswordReset([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'expiracion' => date('Y-m-d H:i:s', time() + 3600),
            'usado' => 0,
            'contrasena_temporal' => null,
        ]);
        $registro->id = 1;

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn($registro);

        $passwordResetRepository->expects($this->never())
            ->method('marcarUsado');

        $servicio = $this->crearServicio(
            $this->createMock(UsuarioRepositoryInterface::class),
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de confirmación es inválido');

        $servicio->confirmarCambio('usuario@test.com', 'token-valido');
    }

    public function test_confirmar_cambio_falla_si_el_usuario_no_existe(): void
    {
        $registro = new PasswordReset([
            'email' => 'usuario@test.com',
            'token' => 'token-valido',
            'expiracion' => date('Y-m-d H:i:s', time() + 3600),
            'usado' => 0,
            'contrasena_temporal' => password_hash('nueva123', PASSWORD_DEFAULT),
        ]);
        $registro->id = 1;

        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->once())
            ->method('findByEmail')
            ->with('usuario@test.com')
            ->willReturn(null);

        $usuarioRepository->expects($this->never())
            ->method('update');

        $passwordResetRepository = $this->createMock(
            PasswordResetRepositoryInterface::class
        );

        $passwordResetRepository->expects($this->once())
            ->method('findByTokenAndEmail')
            ->willReturn($registro);

        $passwordResetRepository->expects($this->never())
            ->method('marcarUsado');

        $servicio = $this->crearServicio(
            $usuarioRepository,
            $passwordResetRepository,
            $this->createMock(MailService::class),
            $this->createMock(LogActividadService::class)
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El enlace de confirmación es inválido');

        $servicio->confirmarCambio('usuario@test.com', 'token-valido');
    }
}