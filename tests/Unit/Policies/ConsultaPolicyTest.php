<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Consulta;
use App\Models\Propiedad;
use App\Policies\ConsultaPolicy;
use PHPUnit\Framework\TestCase;

final class ConsultaPolicyTest extends TestCase
{
    private ConsultaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ConsultaPolicy();
    }

    public function test_permite_participar_al_interesado_que_hizo_la_consulta(): void
    {
        $consulta = new Consulta();
        $consulta->usuario_id = 5;

        $this->assertTrue($this->policy->puedeParticipar(5, $consulta));
    }

    public function test_permite_participar_al_dueno_de_la_propiedad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $consulta = new Consulta();
        $consulta->usuario_id = 5;
        $consulta->setRelation('propiedad', $propiedad);

        $this->assertTrue($this->policy->puedeParticipar(9, $consulta));
    }

    public function test_deniega_participacion_a_un_tercero_sin_relacion(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $consulta = new Consulta();
        $consulta->usuario_id = 5;
        $consulta->setRelation('propiedad', $propiedad);

        $this->assertFalse($this->policy->puedeParticipar(14, $consulta));
    }
}