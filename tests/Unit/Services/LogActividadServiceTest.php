<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\LogActividadRepositoryInterface;
use App\Services\LogActividadService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class LogActividadServiceTest extends TestCase
{
    public function test_lista_los_logs(): void
    {
        $logs = new Collection([
            [
                'id' => 1,
                'usuario_id' => 2,
                'accion' => 'Inicio de sesión',
            ],
        ]);

        $repository = $this->createMock(
            LogActividadRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($logs);

        $service = new LogActividadService($repository);

        $this->assertSame($logs, $service->listar());
    }

    public function test_obtener_devuelve_un_log_existente(): void
    {
        $log = [
            'id' => 1,
            'usuario_id' => 2,
            'accion' => 'Inicio de sesión',
        ];

        $repository = $this->createMock(
            LogActividadRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($log);

        $service = new LogActividadService($repository);

        $this->assertSame($log, $service->obtener(1));
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            LogActividadRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new LogActividadService($repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Log no encontrado');

        $service->obtener(1);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            LogActividadRepositoryInterface::class
        );

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new LogActividadService($repository);

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }

    public function test_registra_un_log(): void
    {
        $repository = $this->createMock(
            LogActividadRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['usuario_id'] === 2
                    && $data['accion'] === 'Inicio de sesión'
                    && $data['ip_address'] === '127.0.0.1'
                    && preg_match(
                        '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                        $data['fecha']
                    ) === 1;
            }));

        $service = new LogActividadService($repository);

        $service->registrar(
            2,
            ' Inicio de sesión ',
            '127.0.0.1'
        );

        $this->addToAssertionCount(1);
    }
}