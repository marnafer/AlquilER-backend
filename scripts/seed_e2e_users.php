<?php

declare(strict_types=1);

/**
 * Crea o actualiza los usuarios que usa la suite E2E de Playwright.
 *
 * Uso:
 *   php scripts/seed_e2e_users.php
 *
 * Es idempotente: si el usuario ya existe, le renueva la contraseña, lo marca
 * como verificado y le asigna el rol correspondiente. La contraseña coincide
 * con la que esperan los specs (tests/e2e/helpers.js).
 */

use App\Models\Usuario;
use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

require_once dirname(__DIR__) . '/src/database.php';

const E2E_PASSWORD = 'E2e123456';

$usuarios = [
    ['email' => 'e2e.admin@test.com', 'rol_id' => 2],
    ['email' => 'e2e.inquilino@test.com', 'rol_id' => 1],
];

foreach ($usuarios as $datos) {
    $usuario = Usuario::where('email', $datos['email'])->first();

    if (!$usuario) {
        $usuario = new Usuario([
            'nombre' => 'E2E',
            'apellido' => 'Test',
            'email' => $datos['email'],
            'telefono' => '1155558899',
            'domicilio' => 'Calle E2E 123',
            'contrasena' => password_hash(E2E_PASSWORD, PASSWORD_DEFAULT),
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        $usuario->forceFill(['rol_id' => $datos['rol_id']])->save();
        echo "creado {$datos['email']} (rol {$datos['rol_id']})\n";
        continue;
    }

    $usuario->email_verified_at = date('Y-m-d H:i:s');
    $usuario->contrasena = password_hash(E2E_PASSWORD, PASSWORD_DEFAULT);
    $usuario->forceFill(['rol_id' => $datos['rol_id']])->save();
    echo "actualizado {$datos['email']} (rol {$datos['rol_id']})\n";
}

echo 'propiedades en la base: ' . \App\Models\Propiedad::count() . "\n";
