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

        $tokens = $this->tokenProvider->generateTokens($usuario);

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token' => $tokens['access_token'], // Retrocompatibilidad
            'rol_id' => $usuario->rol_id,
            'usuario_id' => $usuario->id,
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

        $usuario = $this->usuarioRepository->createWithRole($data, 1);

        $this->logActividadService->registrar(
            $usuario->id,
            'Registro de usuario'
        );

        return $usuario;
    }

    /**
     * Refresca el access token usando un refresh token válido
     */
    public function refresh(string $refreshToken): array
    {
        // Validar que el token sea un refresh token válido
        $payload = $this->tokenProvider->validateRefreshToken($refreshToken);

        if (!$payload) {
            throw new UnauthorizedException('Refresh token inválido o expirado');
        }

        // Obtener el usuario
        $usuario = $this->usuarioRepository->findById((int) $payload->sub);

        if (!$usuario) {
            throw new UnauthorizedException('Usuario no encontrado');
        }

        // Generar un nuevo access token
        $accessToken = $this->tokenProvider->generateAccessToken($usuario);

        return [
            'access_token' => $accessToken,
            'token' => $accessToken, // Retrocompatibilidad
            'refresh_token' => $refreshToken, // Devolver el mismo refresh token
        ];
    }
}