<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Propiedad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Policies\ReservaPolicy;
use PHPUnit\Framework\TestCase;

final class ReservaPolicyTest extends TestCase
{
    private ReservaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ReservaPolicy();
    }

    public function test_puede_ver_permite_al_administrador(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', null);

        $this->assertTrue($this->policy->puedeVer(99, Rol::ADMIN, $reserva));
    }

    public function test_puede_ver_permite_al_inquilino(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', null);

        $this->assertTrue($this->policy->puedeVer(5, Rol::USUARIO, $reserva));
    }

    public function test_puede_ver_permite_al_propietario_de_la_propiedad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertTrue($this->policy->puedeVer(9, Rol::USUARIO, $reserva));
    }

    public function test_puede_ver_deniega_a_un_tercero(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertFalse($this->policy->puedeVer(14, Rol::USUARIO, $reserva));
    }

    public function test_puede_crear_permite_a_un_usuario_distinto_del_propietario(): void
    {
        $this->assertTrue($this->policy->puedeCrear(5, 9));
    }

    public function test_puede_crear_deniega_al_propio_propietario(): void
    {
        $this->assertFalse($this->policy->puedeCrear(5, 5));
    }

    public function test_puede_confirmar_permite_al_administrador(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', null);

        $this->assertTrue($this->policy->puedeConfirmar(99, Rol::ADMIN, $reserva));
    }

    public function test_puede_confirmar_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertTrue($this->policy->puedeConfirmar(9, Rol::USUARIO, $reserva));
    }

    public function test_puede_confirmar_deniega_al_inquilino(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertFalse($this->policy->puedeConfirmar(5, Rol::USUARIO, $reserva));
    }

    public function test_puede_rechazar_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertTrue($this->policy->puedeRechazar(9, Rol::USUARIO, $reserva));
    }

    public function test_puede_rechazar_deniega_al_inquilino(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertFalse($this->policy->puedeRechazar(5, Rol::USUARIO, $reserva));
    }

    public function test_puede_finalizar_permite_al_propietario(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertTrue($this->policy->puedeFinalizar(9, Rol::USUARIO, $reserva));
    }

    public function test_puede_finalizar_deniega_al_inquilino(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 9;

        $reserva = new Reserva();
        $reserva->usuario_id = 5;
        $reserva->setRelation('propiedad', $propiedad);

        $this->assertFalse($this->policy->puedeFinalizar(5, Rol::USUARIO, $reserva));
    }

    public function test_puede_cancelar_permite_al_inquilino(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;

        $this->assertTrue($this->policy->puedeCancelar(5, Rol::USUARIO, $reserva));
    }

    public function test_puede_cancelar_permite_al_administrador(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;

        $this->assertTrue($this->policy->puedeCancelar(99, Rol::ADMIN, $reserva));
    }

    public function test_puede_cancelar_deniega_a_otro_usuario(): void
    {
        $reserva = new Reserva();
        $reserva->usuario_id = 5;

        $this->assertFalse($this->policy->puedeCancelar(9, Rol::USUARIO, $reserva));
    }

    public function test_solo_administrador_puede_modificar(): void
    {
        $this->assertTrue($this->policy->puedeModificar(Rol::ADMIN));
        $this->assertFalse($this->policy->puedeModificar(Rol::USUARIO));
    }

    public function test_solo_administrador_puede_eliminar(): void
    {
        $this->assertTrue($this->policy->puedeEliminar(Rol::ADMIN));
        $this->assertFalse($this->policy->puedeEliminar(Rol::USUARIO));
    }

    public function test_solo_administrador_puede_restaurar(): void
    {
        $this->assertTrue($this->policy->puedeRestaurar(Rol::ADMIN));
        $this->assertFalse($this->policy->puedeRestaurar(Rol::USUARIO));
    }
}
