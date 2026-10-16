<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Rol;
use App\Policies\UsuarioPolicy;
use PHPUnit\Framework\TestCase;

final class UsuarioPolicyTest extends TestCase
{
    private UsuarioPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UsuarioPolicy();
    }

    public function test_permite_ver_al_propio_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeVer(5, Rol::USUARIO, 5)
        );
    }

    public function test_permite_a_un_administrador_ver_cualquier_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeVer(1, Rol::ADMIN, 5)
        );
    }

    public function test_deniega_ver_a_otro_usuario_sin_permisos(): void
    {
        $this->assertFalse(
            $this->policy->puedeVer(1, Rol::USUARIO, 5)
        );
    }

    public function test_permite_actualizar_al_propio_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeActualizar(5, Rol::USUARIO, 5)
        );
    }

    public function test_permite_a_un_administrador_actualizar_cualquier_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeActualizar(1, Rol::ADMIN, 5)
        );
    }

    public function test_deniega_actualizar_a_otro_usuario_sin_permisos(): void
    {
        $this->assertFalse(
            $this->policy->puedeActualizar(1, Rol::USUARIO, 5)
        );
    }

    public function test_permite_eliminar_al_propio_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeEliminar(5, Rol::USUARIO, 5)
        );
    }

    public function test_permite_a_un_administrador_eliminar_cualquier_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeEliminar(1, Rol::ADMIN, 5)
        );
    }

    public function test_deniega_eliminar_a_otro_usuario_sin_permisos(): void
    {
        $this->assertFalse(
            $this->policy->puedeEliminar(1, Rol::USUARIO, 5)
        );
    }

    public function test_solo_permite_administrar_al_rol_correspondiente(): void
    {
        $this->assertTrue(
            $this->policy->puedeAdministrar(Rol::ADMIN)
        );

        $this->assertFalse(
            $this->policy->puedeAdministrar(Rol::USUARIO)
        );
    }

    public function test_deniega_ver_el_propio_perfil_publico(): void
    {
        $this->assertFalse(
            $this->policy->puedeVerPerfilPublico(5, Rol::USUARIO, 5, true)
        );
    }

    public function test_permite_a_un_administrador_ver_el_perfil_publico_de_otro_usuario(): void
    {
        $this->assertTrue(
            $this->policy->puedeVerPerfilPublico(1, Rol::ADMIN, 5, false)
        );
    }

    public function test_permite_ver_el_perfil_publico_si_existe_una_relacion(): void
    {
        $this->assertTrue(
            $this->policy->puedeVerPerfilPublico(1, Rol::USUARIO, 5, true)
        );
    }

    public function test_deniega_ver_el_perfil_publico_sin_relacion(): void
    {
        $this->assertFalse(
            $this->policy->puedeVerPerfilPublico(1, Rol::USUARIO, 5, false)
        );
    }
}