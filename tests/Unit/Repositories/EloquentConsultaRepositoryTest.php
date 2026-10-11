<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Consulta;
use App\Repositories\EloquentConsultaRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class EloquentConsultaRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $schema = $capsule->schema();
        $schema->create('consultas', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('propiedad_id');
            $table->unsignedInteger('usuario_id');
            $table->dateTime('fecha_consulta');
            $table->dateTime('deleted_at')->nullable();
        });
        $schema->create('mensajes_consultas', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('consulta_id');
            $table->unsignedInteger('usuario_id');
            $table->text('mensaje');
            $table->dateTime('fecha_mensaje');
            $table->dateTime('deleted_at')->nullable();
        });
        $schema->create('usuarios', function ($table): void {
            $table->increments('id');
            $table->dateTime('deleted_at')->nullable();
        });

        $capsule->table('usuarios')->insert([
            ['id' => 3],
            ['id' => 4],
        ]);
        $capsule->table('consultas')->insert([
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 3,
                'fecha_consulta' => '2026-10-01 10:00:00',
            ],
            [
                'id' => 11,
                'propiedad_id' => 20,
                'usuario_id' => 4,
                'fecha_consulta' => '2026-10-05 10:00:00',
            ],
            [
                'id' => 12,
                'propiedad_id' => 20,
                'usuario_id' => 3,
                'fecha_consulta' => '2026-10-03 10:00:00',
            ],
        ]);
        $capsule->table('mensajes_consultas')->insert([
            [
                'consulta_id' => 10,
                'usuario_id' => 3,
                'mensaje' => 'Actividad reciente',
                'fecha_mensaje' => '2026-10-10 10:00:00',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_get_by_propiedad_ordena_por_ultima_actividad(): void
    {
        $consultas = (new EloquentConsultaRepository())
            ->getByPropiedad(20);

        $this->assertSame(
            [10, 11, 12],
            array_column($consultas, 'id')
        );
        $this->assertSame(
            '2026-10-10 10:00:00',
            $consultas[0]['ultima_actividad']
        );
    }
}
