<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\TokenProviderInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Sanitizers\UsuarioSanitizer;
use App\Validators\UsuarioValidator;
use App\Models\Usuario;

class AutenticadorService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepository,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly LogActividadService $logActividadService
    ) {
    }

    public function login(array $rawData): array
    {
        $email = $rawData['email'] ?? null;
        $contrasena = $rawData['contrasena'] ?? null;

        if (!$email || !$contrasena) {
            throw new ValidationException([
                'credenciales' => [
                    'Email y contraseña son obligatorios',
                ],
            ]);
        }

        $email = UsuarioSanitizer::sanitizarSoloEmail($email);

        $validacion = UsuarioValidator::validarEmailLoginUsuario($email);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $usuario = $this->usuarioRepository->findByEmail($email);

        if (
            !$usuario
            || !password_verify($contrasena, $usuario->contrasena)
        ) {
            throw new UnauthorizedException('Credenciales inválidas');
        }

        $this->logActividadService->registrar(
            $usuario->id,
            'Inicio de sesión'
        );

        return [
            'token' => $this->tokenProvider->generate($usuario),
            'rol_id' => $usuario->rol_id,
        ];
    }

    public function registrar(array $rawData): Usuario
    {
        $data = UsuarioSanitizer::sanitizarUsuario($rawData);

        // El rol público no lo decide el cliente.
        unset($data['rol_id'], $data['id'], $data['deleted_at']);

        $validacion = UsuarioValidator::validarRegistro($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if ($this->usuarioRepository->existsByEmail($data['email'])) {
            throw new ValidationException([
                'email' => [
                    'El usuario ya existe',
                ],
            ]);
        }

        $data['contrasena'] = password_hash(
            $data['contrasena'],
            PASSWORD_DEFAULT
        );

        $this->logActividadService->registrar(
            $data['id'],
            'Registro de usuario'
        );

        return $this->usuarioRepository->createWithRole($data, 1);
    }
}