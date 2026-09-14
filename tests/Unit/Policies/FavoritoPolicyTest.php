<?php

namespace Tests\Unit\Policies;

use App\Models\Favorito;
use App\Policies\FavoritoPolicy;
use PHPUnit\Framework\TestCase;

class FavoritoPolicyTest extends TestCase
{
    private FavoritoPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FavoritoPolicy();
    }

    public function test_usuario_puede_ver_sus_propios_favoritos(): void
    {
        $resultado = $this->policy->puedeVerDeUsuario(
            5,
            1,
            5
        );

        $this->assertTrue($resultado);
    }

    public function test_usuario_no_puede_ver_favoritos_de_otro_usuario(): void
    {
        $resultado = $this->policy->puedeVerDeUsuario(
            5,
            1,
            8
        );

        $this->assertFalse($resultado);
    }

    public function test_administrador_puede_ver_favoritos_de_otro_usuario(): void
    {
        $resultado = $this->policy->puedeVerDeUsuario(
            5,
            2,
            8
        );

        $this->assertTrue($resultado);
    }

    public function test_usuario_puede_eliminar_su_propio_favorito(): void
    {
        $favorito = new Favorito();
        $favorito->usuario_id = 5;
        $favorito->propiedad_id = 10;

        $resultado = $this->policy->puedeEliminar(
            5,
            $favorito
        );

        $this->assertTrue($resultado);
    }

    public function test_usuario_no_puede_eliminar_favorito_de_otro_usuario(): void
    {
        $favorito = new Favorito();
        $favorito->usuario_id = 8;
        $favorito->propiedad_id = 10;

        $resultado = $this->policy->puedeEliminar(
            5,
            $favorito
        );

        $this->assertFalse($resultado);
    }

    public function test_administrador_no_puede_eliminar_favorito_de_otro_usuario(): void
    {
        $favorito = new Favorito();
        $favorito->usuario_id = 8;
        $favorito->propiedad_id = 10;

        $resultado = $this->policy->puedeEliminar(
            5,
            $favorito
        );

        $this->assertFalse($resultado);
    }
}