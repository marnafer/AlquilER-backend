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
use App\Validators\UsuarioValidator;

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
        $tokenHash = hash('sha256', $token);

        $this->passwordResetRepository->create([
            'email' => $email,
            'token' => $tokenHash,
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
                . '<p>Si no solicitaste este cambio, podés ignorar este correo. '
                . 'Tu contraseña actual sigue siendo válida; por seguridad, '
                . 'cambiá tu contraseña si sospechás que alguien más la conoce.</p>',
            'Hola,'
                . "\n\nRecibimos una solicitud para restablecer tu contraseña."
                . "\n\nPara restablecerla, ingresá en el siguiente enlace:\n"
                . $enlace
                . "\n\nEl enlace es válido por " . self::MINUTOS_VIGENCIA . ' minutos.'
                . "\n\nSi no solicitaste este cambio, podés ignorar este correo. "
                . 'Tu contraseña actual sigue siendo válida; por seguridad, '
                . 'cambiá tu contraseña si sospechás que alguien más la conoce.'
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

        $this->mailService->enviar(
            $usuario->email,
            'Tu contraseña fue restablecida',
            '<p>Hola,</p>'
                . '<p>Tu contraseña se <strong>restableció correctamente</strong>.</p>'
                . '<p>Ya podés iniciar sesión con tu nueva contraseña.</p>'
                . '<p>Si no realizaste este cambio, contactanos de inmediato '
                . 'respondiendo a este correo.</p>',
            'Hola,'
                . "\n\nTu contraseña se restableció correctamente."
                . "\n\nYa podés iniciar sesión con tu nueva contraseña."
                . "\n\nSi no realizaste este cambio, contactanos de inmediato respondiendo a este correo."
        );
    }

    /**
     * Flujo desde "Mi Perfil": valida la contraseña actual, guarda la nueva
     * (hasheada) asociada a un token y envía el correo de confirmación.
     * La contraseña NO se cambia hasta que el usuario confirma el enlace.
     */
    public function solicitarCambioDesdePerfil(
        Usuario $usuario,
        string $contrasenaActual,
        string $contrasenaNueva
    ): void {
        if (
            !password_verify(
                $contrasenaActual,
                (string) $usuario->contrasena
            )
        ) {
            throw new ValidationException([
                'contrasena_actual' => [
                    'La contraseña actual no es correcta',
                ],
            ]);
        }

        $validacion = UsuarioValidator::validarContrasena(
            $contrasenaNueva
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'contrasena' => [$validacion['error']],
            ]);
        }

        $email = strtolower((string) $usuario->email);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $this->passwordResetRepository->create([
            'email' => $email,
            'token' => $tokenHash,
            'expiracion' => date(
                'Y-m-d H:i:s',
                strtotime('+' . self::MINUTOS_VIGENCIA . ' minutes')
            ),
            'usado' => 0,
            'contrasena_temporal' => password_hash(
                $contrasenaNueva,
                PASSWORD_DEFAULT
            ),
        ]);

        $enlace = rtrim($this->frontendUrl, '/')
            . '/restablecer-contrasena?confirmar=1&token='
            . urlencode($token)
            . '&email='
            . urlencode($email);

        $this->mailService->enviar(
            $email,
            'Confirmá el cambio de contraseña',
            '<p>Hola,</p>'
                . '<p>Pediste cambiar tu contraseña. Para confirmarlo, '
                . 'hacé clic en el siguiente enlace:</p>'
                . '<p><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">'
                . 'Confirmar cambio de contraseña</a></p>'
                . '<p>El enlace es válido por ' . self::MINUTOS_VIGENCIA . ' minutos.</p>'
                . '<p>Tu contraseña actual sigue vigente hasta que confirmes este cambio.</p>'
                . '<p>Si no solicitaste este cambio, podés ignorar este correo.</p>',
            'Hola,'
                . "\n\nPediste cambiar tu contraseña."
                . "\n\nPara confirmarlo, ingresá en el siguiente enlace:\n"
                . $enlace
                . "\n\nEl enlace es válido por " . self::MINUTOS_VIGENCIA . ' minutos.'
                . "\n\nTu contraseña actual sigue vigente hasta que confirmes este cambio."
                . "\n\nSi no solicitaste este cambio, podés ignorar este correo."
        );
    }

    /**
     * Aplica el cambio pendiente al abrir el enlace recibido por correo.
     * No requiere que el usuario vuelva a escribir la contraseña.
     */
    public function confirmarCambio(string $email, string $token): void
    {
        $email = strtolower(trim($email));
        $token = trim($token);

        $registro = $this->passwordResetRepository->findByTokenAndEmail(
            $token,
            $email
        );

        if (!$registro) {
            throw new BadRequestException(
                'El enlace de confirmación es inválido'
            );
        }

        if ((int) $registro->usado === 1) {
            throw new BadRequestException(
                'Este cambio de contraseña ya fue confirmado'
            );
        }

        if (
            strtotime((string) $registro->expiracion)
            < time()
        ) {
            throw new BadRequestException(
                'El enlace de confirmación expiró'
            );
        }

        $contrasenaHash = $registro->contrasena_temporal;

        if (!$contrasenaHash) {
            throw new BadRequestException(
                'El enlace de confirmación es inválido'
            );
        }

        $usuario = $this->usuarioRepository->findByEmail($email);

        if (!$usuario) {
            throw new BadRequestException(
                'El enlace de confirmación es inválido'
            );
        }

        $this->usuarioRepository->update(
            $usuario,
            ['contrasena' => $contrasenaHash]
        );

        $this->passwordResetRepository->marcarUsado(
            (int) $registro->id
        );

        $this->logActividadService->registrar(
            $usuario->id,
            'Confirmación de cambio de contraseña'
        );

        $this->mailService->enviar(
            $usuario->email,
            'Tu contraseña fue actualizada',
            '<p>Hola,</p>'
                . '<p>Tu contraseña se <strong>actualizó correctamente</strong>.</p>'
                . '<p>Ya podés iniciar sesión con tu nueva contraseña.</p>'
                . '<p>Si no realizaste este cambio, contactanos de inmediato.</p>',
            'Hola,'
                . "\n\nTu contraseña se actualizó correctamente."
                . "\n\nYa podés iniciar sesión con tu nueva contraseña."
                . "\n\nSi no realizaste este cambio, contactanos de inmediato."
        );
    }
}