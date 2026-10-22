<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Consulta;
use App\Models\Favorito;
use App\Models\Localidad;
use App\Models\MensajeConsulta;
use App\Models\Notificacion;
use App\Models\Propiedad;
use App\Models\RefreshToken;
use App\Models\Usuario;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

final class ModelRelationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_consulta_tiene_mensajes(): void
    {
        $this->assertNotNull((new Consulta())->mensajes());
    }

    public function test_favorito_tiene_propiedad(): void
    {
        $this->assertNotNull((new Favorito())->propiedad());
        $this->assertNotNull((new Favorito())->usuario());
    }

    public function test_localidad_tiene_propiedades(): void
    {
        $this->assertNotNull((new Localidad())->propiedades());
        $this->assertNotNull((new Localidad())->provincia());
    }

    public function test_mensaje_consulta_tiene_consulta(): void
    {
        $this->assertNotNull((new MensajeConsulta())->consulta());
    }

    public function test_notificacion_tiene_usuario(): void
    {
        $this->assertNotNull((new Notificacion())->usuario());
    }

    public function test_refresh_token_tiene_usuario(): void
    {
        $this->assertNotNull((new RefreshToken())->usuario());
    }

    public function test_propiedad_tiene_reservas_consultas_y_favoritos(): void
    {
        $propiedad = new Propiedad();

        $this->assertNotNull($propiedad->reservas());
        $this->assertNotNull($propiedad->consultas());
        $this->assertNotNull($propiedad->favoritos());
    }

    public function test_usuario_tiene_consultas_favoritos_propiedades_y_reservas(): void
    {
        $usuario = new Usuario();

        $this->assertNotNull($usuario->consultas());
        $this->assertNotNull($usuario->favoritos());
        $this->assertNotNull($usuario->propiedades());
        $this->assertNotNull($usuario->reservas());
    }
}
