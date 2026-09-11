<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Categoria;
use App\Models\Provincia;
use App\Models\Localidad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Servicio;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use App\Models\Reserva;
use App\Models\Resena;
use App\Models\Consulta;
use App\Models\Favorito;
use App\Models\LogActividad;

class IntegridadBaseDeDatosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            Capsule::connection();
        } catch (\Throwable $e) {
            $capsule = new Capsule;
            
            $capsule->addConnection([
                'driver'    => 'mysql',
                'host'      => '127.0.0.1',
                'database'  => 'sistema_alquiler_db_dev',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'prefix'    => '',
            ]);

            $capsule->setAsGlobal();
            $capsule->bootEloquent();
        }
    }

    /** @test */
    public function test_it_verifies_all_database_tables_and_relations()
    {
        // 1. Tablas base independientes (usando query builder para evitar conflictos de soft deletes ausentes)
        $this->assertGreaterThan(0, Capsule::table('categorias')->count(), 'La tabla categorias está vacía.');
        $this->assertGreaterThan(0, Capsule::table('provincias')->count(), 'La tabla provincias está vacía.');
        $this->assertGreaterThan(0, Capsule::table('roles')->count(), 'La tabla roles está vacía.');
        $this->assertGreaterThan(0, Capsule::table('servicios')->count(), 'La tabla servicios está vacía.');

        // 2. Localidades y Relación
        $this->assertGreaterThan(0, Localidad::count(), 'La tabla localidades está vacía.');
        $localidad = Localidad::with('provincia')->first();
        if ($localidad) {
            $this->assertNotNull($localidad->provincia, 'Relación Localidad -> Provincia rota.');
        }

        // 3. Usuarios y Relación
        $this->assertGreaterThan(0, Usuario::count(), 'La tabla usuarios está vacía.');
        $usuario = Usuario::with('rol')->first();
        if ($usuario) {
            $this->assertNotNull($usuario->rol, 'Relación Usuario -> Rol rota.');
        }

        // 4. Propiedades y Relaciones principales
        $propiedadesCount = Propiedad::count();
        if ($propiedadesCount > 0) {
            $propiedad = Propiedad::with(['categoria', 'localidad', 'usuario'])->first();
            $this->assertNotNull($propiedad->categoria, 'Relación Propiedad -> Categoria rota.');
            $this->assertNotNull($propiedad->localidad, 'Relación Propiedad -> Localidad rota.');
            $this->assertNotNull($propiedad->usuario, 'Relación Propiedad -> Usuario rota.');
        }

        // 5. Imágenes de Propiedades
        if (PropiedadImagen::count() > 0) {
            $imagen = PropiedadImagen::with('propiedad')->first();
            $this->assertNotNull($imagen->propiedad, 'Relación PropiedadImagen -> Propiedad rota.');
        }

        // 6. Reservas y Relaciones
        if (Reserva::count() > 0) {
            $reserva = Reserva::with(['propiedad', 'usuario'])->first();
            $this->assertNotNull($reserva->propiedad, 'Relación Reserva -> Propiedad rota.');
            $this->assertNotNull($reserva->usuario, 'Relación Reserva -> Usuario rota.');
        }

        // 7. Reseñas y Relaciones
        if (Resena::count() > 0) {
            $resena = Resena::with('reserva')->first();
            $this->assertNotNull($resena->reserva, 'Relación Resena -> Reserva rota.');
        }

        // 8. Consultas y Relaciones
        if (Consulta::count() > 0) {
            $consulta = Consulta::with(['propiedad', 'usuario'])->first();
            $this->assertNotNull($consulta->propiedad, 'Relación Consulta -> Propiedad rota.');
            $this->assertNotNull($consulta->usuario, 'Relación Consulta -> Usuario rota.');
        }

        // 9. Favoritos y Relaciones
        if (Favorito::count() > 0) {
            $favorito = Favorito::with(['usuario', 'propiedad'])->first();
            $this->assertNotNull($favorito->usuario, 'Relación Favorito -> Usuario rota.');
            $this->assertNotNull($favorito->propiedad, 'Relación Favorito -> Propiedad rota.');
        }

        // 10. Logs de Actividad
        if (LogActividad::count() > 0) {
            $log = LogActividad::with('usuario')->first();
            $this->assertNotNull($log->usuario, 'Relación LogActividad -> Usuario rota.');
        }

        $this->assertTrue(true);
    }
}