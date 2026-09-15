<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use App\Policies\PropiedadImagenPolicy;
use PHPUnit\Framework\TestCase;

final class PropiedadImagenPolicyTest extends TestCase
{
    public function test_gestionar_propiedad_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $user = (object) [
            'sub' => 7,
            'rol_id' => 1,
        ];

        $policy = new PropiedadImagenPolicy();

        $policy->gestionarPropiedad($propiedad, $user);

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_propiedad_permite_al_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $user = (object) [
            'sub' => 99,
            'rol_id' => 2,
        ];

        $policy = new PropiedadImagenPolicy();

        $policy->gestionarPropiedad($propiedad, $user);

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_propiedad_lanza_excepcion_si_no_es_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $user = (object) [
            'sub' => 99,
            'rol_id' => 1,
        ];

        $policy = new PropiedadImagenPolicy();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tiene permisos sobre esta propiedad'
        );

        $policy->gestionarPropiedad($propiedad, $user);
    }

    public function test_gestionar_imagen_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->propiedad_id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $user = (object) [
            'sub' => 7,
            'rol_id' => 1,
        ];

        $policy = new PropiedadImagenPolicy();

        $policy->gestionar($imagen, $user);

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_imagen_permite_al_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->propiedad_id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $user = (object) [
            'sub' => 99,
            'rol_id' => 2,
        ];

        $policy = new PropiedadImagenPolicy();

        $policy->gestionar($imagen, $user);

        $this->addToAssertionCount(1);
    }

    public function test_gestionar_imagen_lanza_excepcion_si_no_es_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->propiedad_id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $user = (object) [
            'sub' => 99,
            'rol_id' => 1,
        ];

        $policy = new PropiedadImagenPolicy();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tiene permisos sobre esta imagen'
        );

        $policy->gestionar($imagen, $user);
    }
}