<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use App\Models\Usuario;
use App\Repositories\PasswordResetRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Sanitizers\RecuperarContrasenaSanitizer;
use App\Validators\RecuperarContrasenaValidator;

class RecuperarContrasenaService
{
    private const MINUTOS_VIGENCIA = 60;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepository,
        private readonly PasswordResetRepositoryInterface $passwordResetRepository,
        private readonly MailService $mailService,
        private readonly LogActividadService $logActividadService,
        private readonly string $frontendUrl
    ) {
    }

    /**
     * Genera el token de recuperación y envía el correo con el enlace.
     * No revela si el email existe (anti-enumeración).
     */
    public function solicitar(array $rawData): void
    {
        $data = RecuperarContrasenaSanitizer::sanitizarSolicitud($rawData);

        $validacion = RecuperarContrasenaValidator::validarSolicitud($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $email = strtolower(trim($data['email']));

        $usuario = $this->usuarioRepository->findByEmail($email);

        if (!$usuario) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        $this->passwordResetRepository->create([
            'email' => $email,
            'token' => $token,
            'expiracion' => date(
                'Y-m-d H:i:s',
                strtotime('+' . self::MINUTOS_VIGENCIA . ' minutes')
            ),
            'usado' => 0,
        ]);

        $enlace = rtrim($this->frontendUrl, '/')
            . '/restablecer-contrasena?token='
            . urlencode($token)
            . '&email='
            . urlencode($email);

        $this->mailService->enviar(
            $email,
            'Recuperación de contraseña',
            '<p>Hola,</p>'
                . '<p>Recibimos una solicitud para restablecer tu contraseña.</p>'
                . '<p><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">'
                . 'Hacé clic acá para restablecerla</a></p>'
                . '<p>El enlace es válido por ' . self::MINUTOS_VIGENCIA . ' minutos.</p>'
                . '<p>Si no solicitaste este cambio, podés ignorar este correo.</p>',
            'Hola,'
                . "\n\nRecibimos una solicitud para restablecer tu contraseña."
                . "\n\nPara restablecerla, ingresá en el siguiente enlace:\n"
                . $enlace
                . "\n\nEl enlace es válido por " . self::MINUTOS_VIGENCIA . ' minutos.'
                . "\n\nSi no solicitaste este cambio, podés ignorar este correo."
        );
    }

    /**
     * Restablece la contraseña si el token es válido y no está usado.
     */
    public function restablecer(array $rawData): void
    {
        $data = RecuperarContrasenaSanitizer::sanitizarRestablecer($rawData);

        $validacion = RecuperarContrasenaValidator::validarRestablecer($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $email = strtolower(trim($data['email']));
        $token = trim((string) $data['token']);

        $registro = $this->passwordResetRepository->findByTokenAndEmail(
            $token,
            $email
        );

        if (!$registro) {
            throw new BadRequestException(
                'El enlace de recuperación es inválido'
            );
        }

        if ((int) $registro->usado === 1) {
            throw new BadRequestException(
                'El enlace de recuperación ya fue utilizado'
            );
        }

        if (
            strtotime((string) $registro->expiracion)
            < time()
        ) {
            throw new BadRequestException(
                'El enlace de recuperación expiró'
            );
        }

        $usuario = $this->usuarioRepository->findByEmail($email);

        if (!$usuario) {
            throw new BadRequestException(
                'El enlace de recuperación es inválido'
            );
        }

        $this->usuarioRepository->update(
            $usuario,
            [
                'contrasena' => password_hash(
                    $data['contrasena'],
                    PASSWORD_DEFAULT
                ),
            ]
        );

        $this->passwordResetRepository->marcarUsado(
            (int) $registro->id
        );

        $this->logActividadService->registrar(
            $usuario->id,
            'Restablecimiento de contraseña'
        );
    }
}