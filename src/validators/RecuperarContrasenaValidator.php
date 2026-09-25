<?php

declare(strict_types=1);

namespace App\Validators;

class RecuperarContrasenaValidator
{
    /**
     * Valida una solicitud de recuperación de contraseña.
     */
    public static function validarSolicitud(array $data): array
    {
        $errores = [];

        $resultado = UsuarioValidator::validarEmail(
            $data['email'] ?? null
        );

        if (!$resultado['success']) {
            $errores['email'] = $resultado['error'];
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'errors' => $errores,
            ];
        }

        return [
            'success' => true,
            'errors' => null,
        ];
    }

    /**
     * Valida el payload para restablecer la contraseña.
     */
    public static function validarRestablecer(array $data): array
    {
        $errores = [];

        $resultado = UsuarioValidator::validarEmail(
            $data['email'] ?? null
        );

        if (!$resultado['success']) {
            $errores['email'] = $resultado['error'];
        }

        if (
            !isset($data['token'])
            || trim((string) $data['token']) === ''
        ) {
            $errores['token'] = 'El token es requerido';
        }

        $resultado = UsuarioValidator::validarContrasena(
            $data['contrasena'] ?? null
        );

        if (!$resultado['success']) {
            $errores['contrasena'] = $resultado['error'];
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'errors' => $errores,
            ];
        }

        return [
            'success' => true,
            'errors' => null,
        ];
    }
}