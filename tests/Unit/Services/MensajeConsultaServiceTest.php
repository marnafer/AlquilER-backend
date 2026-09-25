<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ValidationException;
use App\Exceptions\ForbiddenException;
use App\Models\Consulta;
use App\Models\MensajeConsulta;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Services\ConsultaService;
use App\Services\LogActividadService;
use App\Services\MensajeConsultaService;
use App\Services\NotificacionService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class MensajeConsultaServiceTest extends TestCase
{
    private $mensajeRepository;
    private $consultaService;
    private $logActividadService;
    private $notificacionService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mensajeRepository = $this->createMock(
            MensajeConsultaRepositoryInterface::class
        );

        $this->consultaService = $this->createMock(
            ConsultaService::class
        );

        $this->logActividadService = $this->createMock(
            LogActividadService::class
        );

        $this->notificacionService = $this->createMock(
            NotificacionService::class
        );

        $this->service = new MensajeConsultaService(
            $this->mensajeRepository,
            $this->consultaService,
            $this->logActividadService,
            $this->notificacionService
        );
    }

    public function test_crear_mensaje_exitosamente(): void
    {
        $consulta = new Consulta();
        $consulta->id = 100;
        $consulta->usuario_id = 5;

        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 5, null)
            ->willReturn($consulta);

        $mensajeEsperado = new MensajeConsulta([
            'mensaje' => 'Hola, me interesa'
        ]);

        $this->mensajeRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['consulta_id'] === 100
                    && $data['usuario_id'] === 5
                    && $data['mensaje'] === 'Hola, me interesa';
            }))
            ->willReturn($mensajeEsperado);

        $this->logActividadService->expects($this->once())
            ->method('registrar')
            ->with(
                5,
                'Envió un mensaje en la consulta #100'
            );

        $this->notificacionService->expects($this->never())
            ->method('crear');

        $resultado = $this->service->crearMensaje([
            'consulta_id' => 100,
            'mensaje' => 'Hola, me interesa'
        ], 5);

        $this->assertSame($mensajeEsperado, $resultado);
    }

    public function test_crear_mensaje_notifica_al_propietario_cuando_escribe_el_interesado(): void
    {
        $consulta = new Consulta();
        $consulta->id = 100;
        $consulta->usuario_id = 5;

        $propiedad = new \App\Models\Propiedad();
        $propiedad->setAttribute('id', 30);
        $propiedad->setAttribute('usuario_id', 7);
        $consulta->setRelation('propiedad', $propiedad);

        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 5, null)
            ->willReturn($consulta);

        $mensajeEsperado = new MensajeConsulta([
            'mensaje' => 'Hola, me interesa'
        ]);

        $this->mensajeRepository->expects($this->once())
            ->method('create')
            ->willReturn($mensajeEsperado);

        $this->logActividadService->expects($this->once())
            ->method('registrar');

        $this->notificacionService->expects($this->once())
            ->method('crear')
            ->with(
                7,
                'mensaje_nuevo',
                'Nuevo mensaje en consulta',
                $this->isType('string'),
                100
            );

        $resultado = $this->service->crearMensaje([
            'consulta_id' => 100,
            'mensaje' => 'Hola, me interesa'
        ], 5);

        $this->assertSame($mensajeEsperado, $resultado);
    }

    public function test_crear_mensaje_notifica_al_interesado_cuando_escribe_el_propietario(): void
    {
        $consulta = new Consulta();
        $consulta->id = 100;
        $consulta->usuario_id = 5;

        $propiedad = new \App\Models\Propiedad();
        $propiedad->setAttribute('id', 30);
        $propiedad->setAttribute('usuario_id', 7);
        $consulta->setRelation('propiedad', $propiedad);

        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 7, null)
            ->willReturn($consulta);

        $mensajeEsperado = new MensajeConsulta([
            'mensaje' => 'Claro, contáctame'
        ]);

        $this->mensajeRepository->expects($this->once())
            ->method('create')
            ->willReturn($mensajeEsperado);

        $this->logActividadService->expects($this->once())
            ->method('registrar');

        $this->notificacionService->expects($this->once())
            ->method('crear')
            ->with(
                5,
                'mensaje_nuevo',
                'Nuevo mensaje en consulta',
                $this->isType('string'),
                100
            );

        $resultado = $this->service->crearMensaje([
            'consulta_id' => 100,
            'mensaje' => 'Claro, contáctame'
        ], 7);

        $this->assertSame($mensajeEsperado, $resultado);
    }

    public function test_crear_mensaje_falla_con_datos_invalidos(): void
    {
        $this->consultaService->expects($this->never())
            ->method('obtenerConsultaAutorizada');

        $this->mensajeRepository->expects($this->never())
            ->method('create');

        $this->logActividadService->expects($this->never())
            ->method('registrar');

        $this->expectException(ValidationException::class);

        $this->service->crearMensaje([
            'consulta_id' => null,
            'mensaje' => ''
        ], 5);
    }

    public function test_crear_mensaje_falla_si_el_mensaje_es_demasiado_largo(): void
    {
        $this->consultaService->expects($this->never())
            ->method('obtenerConsultaAutorizada');

        $this->mensajeRepository->expects($this->never())
            ->method('create');

        $this->logActividadService->expects($this->never())
            ->method('registrar');

        $this->expectException(ValidationException::class);

        $this->service->crearMensaje([
            'consulta_id' => 100,
            'mensaje' => str_repeat('A', 2001)
        ], 5);
    }

    public function test_crear_mensaje_falla_si_usuario_no_esta_autorizado(): void
    {
        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 5, null)
            ->willThrowException(
                new ForbiddenException('No autorizado')
            );

        $this->mensajeRepository->expects($this->never())
            ->method('create');

        $this->logActividadService->expects($this->never())
            ->method('registrar');

        $this->expectException(ForbiddenException::class);

        $this->service->crearMensaje([
            'consulta_id' => 100,
            'mensaje' => 'Hola!'
        ], 5);
    }

    public function test_obtener_historial_exitosamente(): void
    {
        $consulta = new Consulta();
        $consulta->id = 100;

        $mensajes = new Collection([
            new MensajeConsulta([
                'id' => 1,
                'consulta_id' => 100,
                'usuario_id' => 5,
                'mensaje' => 'Hola'
            ]),
            new MensajeConsulta([
                'id' => 2,
                'consulta_id' => 100,
                'usuario_id' => 8,
                'mensaje' => 'Hola, ¿cómo estás?'
            ])
        ]);

        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 5, null)
            ->willReturn($consulta);

        $this->mensajeRepository->expects($this->once())
            ->method('findByConsultaId')
            ->with(100)
            ->willReturn($mensajes);

        $resultado = $this->service->obtenerHistorial(100, 5);

        $this->assertSame($mensajes, $resultado);
    }

    public function test_obtener_historial_falla_si_usuario_no_esta_autorizado(): void
    {
        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 5, null)
            ->willThrowException(
                new ForbiddenException('No autorizado')
            );

        $this->mensajeRepository->expects($this->never())
            ->method('findByConsultaId');

        $this->expectException(ForbiddenException::class);

        $this->service->obtenerHistorial(100, 5);
    }
}