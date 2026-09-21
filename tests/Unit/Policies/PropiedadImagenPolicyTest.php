<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use App\Models\Rol;
use App\Policies\PropiedadImagenPolicy;
use PHPUnit\Framework\TestCase;

final class PropiedadImagenPolicyTest extends TestCase
{
    private PropiedadImagenPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PropiedadImagenPolicy();
    }

    public function test_gestionar_propiedad_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $this->policy->gestionarPropiedad(
            7,
            1,
            $propiedad
        );

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_propiedad_permite_al_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $this->policy->gestionarPropiedad(
            99,
            Rol::ADMIN,
            $propiedad
        );

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_propiedad_lanza_excepcion_si_no_es_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tiene permisos sobre esta propiedad'
        );

        $this->policy->gestionarPropiedad(
            99,
            1,
            $propiedad
        );
    }

    public function test_gestionar_imagen_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->propiedad_id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $this->policy->gestionar(
            7,
            1,
            $imagen
        );

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_imagen_permite_al_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->propiedad_id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $this->policy->gestionar(
            99,
            Rol::ADMIN,
            $imagen
        );

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_imagen_lanza_excepcion_si_no_es_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->propiedad_id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tiene permisos sobre esta imagen'
        );

        $this->policy->gestionar(
            99,
            1,
            $imagen
        );
    }
}