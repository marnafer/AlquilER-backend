<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Creamos la instancia del Capsule Manager
$capsule = new Capsule;

// Configuramos los datos de conexi�n
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// SetAsGlobal permite que Capsule funcione como un "Singleton"
// para que puedas usarlo en cualquier parte del c�digo
$capsule->setAsGlobal();

// Inicializamos Eloquent
$capsule->bootEloquent();

Capsule::connection()->getPdo()->exec("SET NAMES utf8mb4"); // Asegura que la conexi�n use UTF-8