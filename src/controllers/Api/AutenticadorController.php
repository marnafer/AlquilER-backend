<?php

namespace App\Controllers\Api;

use App\Models\Usuario;
use App\Helpers\JwtHelper;
use App\Helpers\Response;
use App\Sanitizers\UsuarioSanitizer;
use App\Validators\UsuarioValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;

class AutenticadorController {

    // LOGIN
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        $email = $data['email'] ?? null;
        $password = $data['contrasena'] ?? null;

        // 1. Validar que vengan los datos
        if (!$email || !$password) {
        throw new ValidationException([
            'credenciales' => [
                'Email y contraseña son obligatorios'
            ]
        ]);
}
        // 2. Sanitizar email
        $email = UsuarioSanitizer::sanitizarSoloEmail($data['email'] ?? null);

        // 3. Validar formato de email
        $validacionEmail = UsuarioValidator::validarEmailLoginUsuario($email);

        if (!$validacionEmail['success']) {
            throw new ValidationException($validacionEmail['errors']);
        }

        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario || !password_verify($password, $usuario->contrasena)) {
            throw new UnauthorizedException('Credenciales inválidas');
        }

        $token = JwtHelper::generarToken($usuario);

        Response::success([
            'token' => $token,
            'rol_id' => $usuario->rol_id
        ]);
    }

    // REGISTER
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        // 1. Sanitizar
        $san = UsuarioSanitizer::sanitizarUsuario($data);

        // 2. Validar
        $val = UsuarioValidator::validarCrearUsuario($san);

        if (!$val['success']) {
            throw new ValidationException($val['errors']);
        }

        // 3. Validación de negocio (email único)
        if (Usuario::where('email', $san['email'])->exists()) {
            throw new ValidationException([
                'email' => 'El usuario ya existe'
            ]);
        }

        // 4. Crear usuario
        Usuario::create([
        'nombre' => $san['nombre'],
        'apellido' => $san['apellido'],
        'email' => $san['email'],
        'telefono' => $san['telefono'],
        'domicilio' => $san['domicilio'],
        'contrasena' => password_hash($san['contrasena'], PASSWORD_BCRYPT),
        'rol_id' => $san['rol_id'] ?? 1
    ]);

        // 5. Respuesta
        Response::created([], 'Usuario registrado');
    }

    // LOGOUT
    public function logout() {
        Response::success(
            [],
            200,
            'Logout (el cliente elimina el token)'
        );
    }
}