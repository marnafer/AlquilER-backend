<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

abstract class RepositorioTestCase extends TestCase
{
    protected Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->crearEsquema();
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();

        parent::tearDown();
    }

    protected function insertar(string $tabla, array $filas): void
    {
        foreach ($filas as $fila) {
            $this->capsule->table($tabla)->insert($fila);
        }
    }

    protected function crearEsquema(): void
    {
        $schema = $this->capsule->schema();

        $schema->create('categorias', function ($t): void {
            $t->increments('id');
            $t->string('nombre');
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('provincias', function ($t): void {
            $t->increments('id');
            $t->string('nombre');
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('localidades', function ($t): void {
            $t->increments('id');
            $t->string('nombre');
            $t->string('codigo_postal')->nullable();
            $t->unsignedInteger('provincia_id');
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('roles', function ($t): void {
            $t->increments('id');
            $t->string('nombre');
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('usuarios', function ($t): void {
            $t->increments('id');
            $t->string('nombre')->nullable();
            $t->string('apellido')->nullable();
            $t->string('email')->nullable();
            $t->string('telefono')->nullable();
            $t->string('domicilio')->nullable();
            $t->string('contrasena')->nullable();
            $t->unsignedInteger('rol_id')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('propiedades', function ($t): void {
            $t->increments('id');
            $t->string('titulo')->nullable();
            $t->text('descripcion')->nullable();
            $t->float('precio')->nullable();
            $t->float('expensas')->nullable();
            $t->string('direccion')->nullable();
            $t->integer('cantidad_ambientes')->nullable();
            $t->integer('cantidad_dormitorios')->nullable();
            $t->integer('cantidad_banos')->nullable();
            $t->integer('capacidad')->nullable();
            $t->boolean('acepta_mascotas')->default(false);
            $t->boolean('acepta_hijos')->default(false);
            $t->text('requisitos_interesados')->nullable();
            $t->boolean('disponible')->default(true);
            $t->boolean('destacada')->default(false);
            $t->unsignedInteger('categoria_id')->nullable();
            $t->unsignedInteger('localidad_id')->nullable();
            $t->unsignedInteger('usuario_id')->nullable();
            $t->dateTime('fecha_publicacion')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('propiedad_imagenes', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('propiedad_id');
            $t->string('ruta');
            $t->string('descripcion')->nullable();
            $t->boolean('es_principal')->default(false);
        });

        $schema->create('servicios', function ($t): void {
            $t->increments('id');
            $t->string('nombre');
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('propiedad_servicio', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('propiedad_id');
            $t->unsignedInteger('servicio_id');
        });

        $schema->create('favoritos', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('usuario_id');
            $t->unsignedInteger('propiedad_id');
        });

        $schema->create('reservas', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('propiedad_id');
            $t->unsignedInteger('usuario_id');
            $t->string('estado')->nullable();
            $t->date('fecha_inicio_alquiler')->nullable();
            $t->date('fecha_fin_alquiler')->nullable();
            $t->dateTime('fecha_confirmacion')->nullable();
            $t->dateTime('fecha_reserva')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('resenas', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('reserva_id');
            $t->string('tipo');
            $t->unsignedInteger('calificador_id');
            $t->integer('calificacion');
            $t->text('comentario')->nullable();
            $t->dateTime('fecha_publicacion')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('notificaciones', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('usuario_id');
            $t->string('tipo');
            $t->string('titulo')->nullable();
            $t->text('mensaje')->nullable();
            $t->unsignedInteger('referencia_id')->nullable();
            $t->boolean('leida')->default(false);
            $t->dateTime('fecha_notificacion')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('mensajes_consultas', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('consulta_id');
            $t->unsignedInteger('usuario_id');
            $t->text('mensaje')->nullable();
            $t->dateTime('fecha_mensaje')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('consultas', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('propiedad_id');
            $t->unsignedInteger('usuario_id');
            $t->dateTime('fecha_consulta')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        $schema->create('logs_actividad', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('usuario_id')->nullable();
            $t->string('accion')->nullable();
            $t->string('ip_address')->nullable();
            $t->dateTime('fecha')->nullable();
        });

        $schema->create('refresh_tokens', function ($t): void {
            $t->increments('id');
            $t->unsignedInteger('usuario_id');
            $t->string('token');
            $t->dateTime('expires_at')->nullable();
            $t->timestamps();
        });

        $schema->create('password_resets', function ($t): void {
            $t->increments('id');
            $t->string('email');
            $t->string('token');
            $t->dateTime('expiracion')->nullable();
            $t->integer('usado')->default(0);
        });
    }
}
