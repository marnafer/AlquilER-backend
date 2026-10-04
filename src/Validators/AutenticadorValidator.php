<?php

declare(strict_types=1);

namespace App\Validators;

class AutenticadorValidator
{
    public static function validarLogin(array $data): array
    {
        $errors = [];

        if ($data['email'] === null) {
            $errors['email'][] = 'El email es obligatorio';
        } elseif (!is_string($data['email'])) {
            $errors['email'][] = 'El email debe ser una cadena de texto';
        }

        if ($data['contrasena'] === null) {
            $errors['contrasena'][] = 'La contraseña es obligatoria';
        } elseif (!is_string($data['contrasena'])) {
            $errors['contrasena'][] = 'La contraseña debe ser una cadena de texto';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
        ];
    }

   public static function validarRefresh(array $data): array
    {
        $errors = [];

        if ($data['refresh_token'] === null) {
            $errors['refresh_token'][] = 'El refresh token es obligatorio';
        } elseif (!is_string($data['refresh_token'])) {
            $errors['refresh_token'][] = 'El refresh token debe ser una cadena de texto';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
        ];
    }
}