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
use App\Sanitizers\AutenticadorSanitizer;
use App\Validators\AutenticadorValidator;
use App\Models\Usuario;
use App\Models\Rol; 
use App\Exceptions\BadRequestException;
use App\Services\MailService;

class AutenticadorService
{

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly LogActividadService $logActividadService,
        private readonly ?MailService $mailService = null,
        private readonly string $frontendUrl = 'http://localhost:3000'
    ) {
    }

    public function login(array $rawData): array
    {
        $data = AutenticadorSanitizer::sanitizarLogin($rawData);

        $validacion = AutenticadorValidator::validarLogin($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $email = UsuarioSanitizer::sanitizarSoloEmail(
            $data['email']
        );

        $validacion = UsuarioValidator::validarEmailLoginUsuario(
            $email
        );

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $usuario = $this->usuarioRepository->findByEmail($email);

        if (
            !$usuario
            || !password_verify(
                $data['contrasena'],
                $usuario->contrasena
            )
        ) {
            throw new UnauthorizedException('Credenciales inválidas');
        }

        if (!$usuario->isEmailVerified()) {
            throw new UnauthorizedException('Email no verificado');
        }

        // Solo 1 refresh token activo por usuario
        $this->refreshTokenRepository->deleteByUsuarioId($usuario->id);

        $accessToken = $this->tokenProvider->generateAccessToken($usuario);
        $refreshToken = $this->tokenProvider->generateRefreshToken();

        $this->refreshTokenRepository->create([
            'usuario_id' => $usuario->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+15 days')
            ),
        ]);

        $this->logActividadService->registrar(
            $usuario->id,
            'Inicio de sesión'
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'rol_id' => $usuario->rol_id,
        ];
    }
    public function registrar(array $rawData): Usuario
    {
        $data = UsuarioSanitizer::sanitizarUsuario($rawData);

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

        $usuario = $this->usuarioRepository->createWithRole(
            $data,
            Rol::USUARIO
        );

        $this->logActividadService->registrar(
            $usuario->id,
            'Registro de usuario'
        );

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $this->usuarioRepository->update($usuario, [
            'email_verification_token' => $tokenHash,
            'email_verification_expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+60 minutes')
            ),
        ]);

        if ($this->mailService !== null) {
            $enlace = rtrim($this->frontendUrl, '/')
                . '/verificar-email?token='
                . urlencode($token)
                . '&email='
                . urlencode((string) $usuario->email);

            $this->mailService->enviar(
                (string) $usuario->email,
                'Verific? tu correo electr?nico',
                '<p>Hola,</p>'
                    . '<p>Gracias por registrarte en AlquilER.</p>'
                    . '<p><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">'
                    . 'Verificar mi correo</a></p>'
                    . '<p>El enlace es v?lido por 60 minutos.</p>',
                'Hola,' . "\n\n" . 'Gracias por registrarte en AlquilER.'
                    . "\n\n" . 'Verific? tu correo ingresando en: ' . $enlace
                    . "\n\n" . 'El enlace es v?lido por 60 minutos.'
            );
        }

        return $usuario;
    }

    public function refresh(array $rawData): array
    {
        $data = AutenticadorSanitizer::sanitizarRefresh($rawData);

        $validacion = AutenticadorValidator::validarRefresh($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $userToken = $this->refreshTokenRepository
            ->findValidByToken($data['refresh_token']);

        if (!$userToken) {
            throw new UnauthorizedException(
                'Refresh token inválido o expirado'
            );
        }

        $usuario = $userToken->usuario;

        if (!$usuario) {
            throw new UnauthorizedException(
                'Usuario no encontrado'
            );
        }

        // Rotación del token: eliminar el usado y crear uno nuevo.
        $this->refreshTokenRepository->deleteById($userToken->id);

        $nuevoAccessToken = $this->tokenProvider->generateAccessToken($usuario);
        $nuevoRefreshToken = $this->tokenProvider->generateRefreshToken();

        $this->refreshTokenRepository->create([
            'usuario_id' => $usuario->id,
            'token' => hash('sha256', $nuevoRefreshToken),
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+15 days')
            ),
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

    public function verificarEmail(array $rawData): void
    {
        $email = strtolower(trim((string) ($rawData['email'] ?? '')));
        $token = trim((string) ($rawData['token'] ?? ''));
        if ($email === '' || $token === '') {
            throw new ValidationException([
                'email' => ['Datos inv?lidos'],
                'token' => ['Datos inv?lidos'],
            ]);
        }

        $usuario = $this->usuarioRepository->findByEmail($email);

        if (!$usuario || $usuario->email_verification_token !== hash('sha256', $token)) {
            throw new BadRequestException('El enlace de verificaci?n es inv?lido');
        }

        if (
            $usuario->email_verification_expires_at === null
            || strtotime((string) $usuario->email_verification_expires_at) < time()
        ) {
            throw new BadRequestException('El enlace de verificaci?n expir?');
        }

        $this->usuarioRepository->update($usuario, [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);
    }
}
