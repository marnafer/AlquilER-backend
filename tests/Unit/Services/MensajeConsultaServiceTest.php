<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Consulta;
use App\Models\MensajeConsulta;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Services\ConsultaService;
use App\Services\LogActividadService;
use App\Services\MensajeConsultaService;
use PHPUnit\Framework\TestCase;

final class MensajeConsultaServiceTest extends TestCase
{
    private $mensajeRepository;
    private $consultaService;
    private $logActividadService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mensajeRepository = $this->createMock(MensajeConsultaRepositoryInterface::class);
        $this->consultaService = $this->createMock(ConsultaService::class);
        $this->logActividadService = $this->createMock(LogActividadService::class);
        
        $this->service = new MensajeConsultaService(
            $this->mensajeRepository,
            $this->consultaService,
            $this->logActividadService
        );
    }

    public function test_crear_mensaje_exitosamente(): void
    {
        $consulta = new Consulta();
        $consulta->id = 100;
        
        $this->consultaService->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(100, 5)
            ->willReturn($consulta);

        $mensajeEsperado = new MensajeConsulta(['mensaje' => 'Hola, me interesa']);

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
            ->with(5, 'Envió un mensaje en la consulta #100');

        $resultado = $this->service->crearMensaje([
            'consulta_id' => 100,
            'mensaje' => 'Hola, me interesa'
        ], 5);

        $this->assertSame($mensajeEsperado, $resultado);
    }
}