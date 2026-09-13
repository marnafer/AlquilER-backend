<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\TokenProviderInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\RefreshTokenRepositoryInterface;
use App\Sanitizers\UsuarioSanitizer;
use App\Validators\UsuarioValidator;
use App\Models\Usuario;
use App\Models\RefreshToken;

class AutenticadorService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
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

        // Solo 1 refresh token activo por usuario

        $this->refreshTokenRepository->deleteByUsuarioId($usuario->id);

        $accessToken = $this->tokenProvider->generateAccessToken($usuario);
        $refreshToken = $this->tokenProvider->generateRefreshToken();

        $this->refreshTokenRepository->create([
            'usuario_id' => $usuario->id,
            'token' => $refreshToken,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 days'))
        ]);

        $this->logActividadService->registrar($usuario->id, 'Inicio de sesión');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'rol_id' => $usuario->rol_id,
        ];
    }

    public function registrar(array $rawData): Usuario
    {
        $data = UsuarioSanitizer::sanitizarUsuario($rawData);

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

    public function refresh(array $rawData): array
    {
        $tokenRecibido = $rawData['refresh_token'] ?? null;

        if (!$tokenRecibido) {
            throw new ValidationException([
                'refresh_token' => ['El refresh token es obligatorio']
            ]);
        }

        $userToken = $this->refreshTokenRepository->findValidByToken($tokenRecibido);

        if (!$userToken) {
            throw new UnauthorizedException('Refresh token inválido o expirado');
        }

        $usuario = $userToken->usuario;

        if (!$usuario) {
            throw new UnauthorizedException('Usuario no encontrado');
        }

        // Rotación del token: eliminar el usado y crear uno nuevo
        $this->refreshTokenRepository->deleteById($userToken->id);

        $nuevoAccessToken = $this->tokenProvider->generateAccessToken($usuario);
        $nuevoRefreshToken = $this->tokenProvider->generateRefreshToken();

        $this->refreshTokenRepository->create([
            'usuario_id' => $usuario->id,
            'token' => $nuevoRefreshToken,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 days'))
        ]);

        return [
            'access_token' => $nuevoAccessToken,
            'refresh_token' => $nuevoRefreshToken,
            'rol_id' => $usuario->rol_id,
        ];
    }

    public function logout(array $rawData): void
    {
        $token = $rawData['refresh_token'] ?? null;

        if ($token) {
            $this->refreshTokenRepository->deleteByToken($token);
        }
    }
}