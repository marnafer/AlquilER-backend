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

    
    public function test_permite_actualizar_al_administrador_o_al_creador(): void
    {
        $consulta = new Consulta();
        $consulta->usuario_id = 5;

        $this->assertTrue($this->policy->puedeActualizar(99, 2, $consulta));
        $this->assertTrue($this->policy->puedeActualizar(5, 1, $consulta));
        $this->assertFalse($this->policy->puedeActualizar(8, 1, $consulta));
    }

    
    public function test_solo_permite_administrar_al_rol_correspondiente(): void
    {
        $this->assertTrue($this->policy->puedeAdministrar(2));
        $this->assertFalse($this->policy->puedeAdministrar(1));
    }

    
    public function test_permite_ver_consultas_de_usuario_al_propietario_o_administrador(): void
    {
        $this->assertTrue($this->policy->puedeVerDeUsuario(1, 2, 5));
        $this->assertTrue($this->policy->puedeVerDeUsuario(5, 1, 5));
        $this->assertFalse($this->policy->puedeVerDeUsuario(3, 1, 5));
    }

    
    public function test_permite_ver_consultas_de_propiedad_al_dueno_o_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 10;

        $this->assertTrue($this->policy->puedeVerDePropiedad(1, 2, $propiedad));
        $this->assertTrue($this->policy->puedeVerDePropiedad(10, 1, $propiedad));
        $this->assertFalse($this->policy->puedeVerDePropiedad(4, 1, $propiedad));
    }
}