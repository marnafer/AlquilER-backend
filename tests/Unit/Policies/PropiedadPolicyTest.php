<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\Rol;
use App\Policies\PropiedadPolicy;
use PHPUnit\Framework\TestCase;

final class PropiedadPolicyTest extends TestCase
{
    private PropiedadPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PropiedadPolicy();
    }

    public function test_permite_gestionar_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 5;

        $this->policy->gestionar($propiedad, 5, Rol::USUARIO);

        $this->addToAssertionCount(1);
    }

    public function test_permite_gestionar_al_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 5;

        $this->policy->gestionar($propiedad, 99, Rol::ADMIN);

        $this->addToAssertionCount(1);
    }

    public function test_deniega_gestionar_a_un_tercero(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 5;

        $this->expectException(ForbiddenException::class);

        $this->policy->gestionar($propiedad, 8, Rol::USUARIO);
    }
}
