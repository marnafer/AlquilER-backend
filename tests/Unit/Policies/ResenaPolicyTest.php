<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\Resena;
use App\Models\Reserva;
use App\Policies\ResenaPolicy;
use PHPUnit\Framework\TestCase;

final class ResenaPolicyTest extends TestCase
{
    private ResenaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ResenaPolicy();
    }

    public function test_permite_al_inquilino_crear_resena_de_propiedad(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;

        $this->policy->crear(
            $reserva,
            5,
            'propiedad'
        );

        $this->addToAssertionCount(1);
    }

    public function test_permite_al_dueno_crear_resena_de_inquilino(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->policy->crear(
            $reserva,
            9,
            'inquilino'
        );

        $this->addToAssertionCount(1);
    }

    public function test_deniega_a_un_tercero_crear_resena_de_propiedad(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;

        $this->expectException(ForbiddenException::class);

        $this->policy->crear(
            $reserva,
            10,
            'propiedad'
        );
    }

    public function test_deniega_a_un_tercero_crear_resena_de_inquilino(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->expectException(ForbiddenException::class);

        $this->policy->crear(
            $reserva,
            10,
            'inquilino'
        );
    }

    public function test_deniega_al_inquilino_crear_resena_de_inquilino(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->expectException(ForbiddenException::class);

        $this->policy->crear(
            $reserva,
            5,
            'inquilino'
        );
    }

    public function test_deniega_al_dueno_crear_resena_de_propiedad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->expectException(ForbiddenException::class);

        $this->policy->crear(
            $reserva,
            9,
            'propiedad'
        );
    }

    public function test_permite_al_autor_eliminar_su_propia_resena(): void
    {
        $resena = new Resena();
        $resena->calificador_id = 5;

        $this->policy->eliminar(
            $resena,
            5,
            1
        );

        $this->addToAssertionCount(1);
    }

    public function test_permite_al_administrador_eliminar_cualquier_resena(): void
    {
        $resena = new Resena();
        $resena->calificador_id = 5;

        $this->policy->eliminar(
            $resena,
            99,
            2
        );

        $this->addToAssertionCount(1);
    }

    public function test_deniega_a_un_tercero_eliminar_resena(): void
    {
        $resena = new Resena();
        $resena->calificador_id = 5;

        $this->expectException(ForbiddenException::class);

        $this->policy->eliminar(
            $resena,
            10,
            1
        );
    }

    public function test_permite_al_administrador_restaurar_resena(): void
    {
        $this->policy->restaurar(2);

        $this->addToAssertionCount(1);
    }

    public function test_deniega_a_un_usuario_restaurar_resena(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->policy->restaurar(1);
    }
}