<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Favorito;
use App\Models\Propiedad;
use App\Policies\FavoritoPolicy;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\FavoritoService;
use App\Services\LogActividadService;
use PHPUnit\Framework\TestCase;

final class FavoritoServiceTest extends TestCase
{
    private $favoritoRepository;
    private $propiedadRepository;
    private $logService;
    private $policyMock;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->favoritoRepository = $this->createMock(
            FavoritoRepositoryInterface::class
        );

        $this->propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->policyMock = $this->createMock(
            FavoritoPolicy::class
        );

        $this->service = new FavoritoService(
            $this->favoritoRepository,
            $this->propiedadRepository,
            $this->logService,
            $this->policyMock
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function test_it_can_list_favoritos_of_authorized_user(): void
    {
        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 5,
                'propiedad_id' => 20
            ]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 5)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getByUserId')
            ->with(5)
            ->willReturn($favoritos);

        $result = $this->service->listar(
            5,
            '5',
            1
        );

        $this->assertSame($favoritos, $result);
    }

    public function test_it_can_list_favoritos_as_admin(): void
    {
        $favoritos = [
            [
                'id' => 15,
                'usuario_id' => 8,
                'propiedad_id' => 30
            ]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 2, 8)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getByUserId')
            ->with(8)
            ->willReturn($favoritos);

        $result = $this->service->listar(
            5,
            '8',
            2
        );

        $this->assertSame($favoritos, $result);
    }

    public function test_it_throws_validation_exception_when_listar_id_is_invalid(): void
    {
        $this->policyMock
            ->expects($this->never())
            ->method('puedeVerDeUsuario');

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getByUserId');

        $this->expectException(ValidationException::class);

        $this->service->listar(
            5,
            'abc',
            1
        );
    }

    public function test_it_throws_validation_exception_when_listar_id_is_zero(): void
    {
        $this->policyMock
            ->expects($this->never())
            ->method('puedeVerDeUsuario');

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getByUserId');

        $this->expectException(ValidationException::class);

        $this->service->listar(
            5,
            '0',
            1
        );
    }

    public function test_it_throws_forbidden_exception_when_user_is_not_authorized(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 8)
            ->willReturn(false);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getByUserId');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para consultar los favoritos de este usuario'
        );

        $this->service->listar(
            5,
            '8',
            1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AGREGAR
    |--------------------------------------------------------------------------
    */

    public function test_it_throws_validation_exception_when_create_data_is_invalid(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->favoritoRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(ValidationException::class);

        $this->service->agregar(
            [
                'propiedad_id' => null
            ],
            5
        );
    }

    public function test_it_throws_validation_exception_when_property_id_is_invalid(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->favoritoRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(ValidationException::class);

        $this->service->agregar(
            [
                'propiedad_id' => 'abc'
            ],
            5
        );
    }

    public function test_it_rejects_invalid_user_id_when_adding_favorito(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->favoritoRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('ID de usuario inválido');

        $this->service->agregar(
            [
                'propiedad_id' => 10
            ],
            0
        );
    }

    public function test_it_throws_exception_when_property_does_not_exist(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(null);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->service->agregar(
            [
                'propiedad_id' => '10'
            ],
            5
        );
    }

    public function test_it_rejects_own_property(): void
    {
        $propiedad = new Propiedad();

        $propiedad->id = 10;
        $propiedad->usuario_id = 5;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'No puedes agregar tu propia propiedad a favoritos'
        );

        $this->service->agregar(
            [
                'propiedad_id' => '10'
            ],
            5
        );
    }

    public function test_it_throws_conflict_when_favorito_already_exists(): void
    {
        $propiedad = new Propiedad();

        $propiedad->id = 10;
        $propiedad->usuario_id = 8;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('add')
            ->with(5, 10)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La propiedad ya está en favoritos'
        );

        $this->service->agregar(
            [
                'propiedad_id' => '10'
            ],
            5
        );
    }

    public function test_it_can_create_favorito_and_register_activity(): void
    {
        $propiedad = new Propiedad();

        $propiedad->id = 10;
        $propiedad->usuario_id = 8;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('add')
            ->with(5, 10)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                5,
                'favorito_agregado'
            );

        $result = $this->service->agregar(
            [
                'propiedad_id' => '10'
            ],
            5
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    public function test_it_throws_validation_exception_when_property_id_is_invalid_on_delete(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('findByUsuarioAndPropiedad');

        $this->expectException(ValidationException::class);

        $this->service->eliminar(
            'abc',
            5
        );
    }

    public function test_it_throws_validation_exception_when_property_id_is_zero_on_delete(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('findByUsuarioAndPropiedad');

        $this->expectException(ValidationException::class);

        $this->service->eliminar(
            '0',
            5
        );
    }

    public function test_it_rejects_invalid_user_id_when_deleting(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('findByUsuarioAndPropiedad');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('ID de usuario inválido');

        $this->service->eliminar(
            '10',
            0
        );
    }

    public function test_it_throws_exception_when_favorito_does_not_exist(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'La propiedad no está en favoritos'
        );

        $this->service->eliminar(
            '10',
            5
        );
    }

    public function test_it_throws_forbidden_exception_when_policy_does_not_allow_delete(): void
    {
        $favorito = new Favorito();

        $favorito->usuario_id = 8;
        $favorito->propiedad_id = 10;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn($favorito);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(5, $favorito)
            ->willReturn(false);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('remove');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para eliminar este favorito'
        );

        $this->service->eliminar(
            '10',
            5
        );
    }

    public function test_it_throws_exception_when_repository_does_not_delete(): void
    {
        $favorito = new Favorito();

        $favorito->usuario_id = 5;
        $favorito->propiedad_id = 10;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn($favorito);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(5, $favorito)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('remove')
            ->with(5, 10)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'No se pudo eliminar el favorito'
        );

        $this->service->eliminar(
            '10',
            5
        );
    }

    public function test_it_can_delete_favorito_and_register_activity(): void
    {
        $favorito = new Favorito();

        $favorito->usuario_id = 5;
        $favorito->propiedad_id = 10;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn($favorito);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(5, $favorito)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('remove')
            ->with(5, 10)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                5,
                'favorito_eliminado'
            );

        $result = $this->service->eliminar(
            '10',
            5
        );

        $this->assertTrue($result);
    }
}