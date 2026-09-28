<?php

declare(strict_types=1);

namespace App\Validators;

class ContactoValidator
{
    public static function validar(array $data): array
    {
        $errores = [];

        $resultado = UsuarioValidator::validarNombre(
            isset($data['nombre']) ? (string) $data['nombre'] : null
        );

        if (!$resultado['success']) {
            $errores['nombre'] = $resultado['error'];
        }

        $resultado = UsuarioValidator::validarEmail(
            isset($data['email']) ? (string) $data['email'] : null
        );

        if (!$resultado['success']) {
            $errores['email'] = $resultado['error'];
        }

        if (
            !isset($data['asunto'])
            || trim((string) $data['asunto']) === ''
        ) {
            $errores['asunto'] = 'El asunto es requerido';
        } elseif (mb_strlen(trim((string) $data['asunto'])) < 3) {
            $errores['asunto'] = 'El asunto debe tener al menos 3 caracteres';
        }

        if (
            !isset($data['mensaje'])
            || trim((string) $data['mensaje']) === ''
        ) {
            $errores['mensaje'] = 'El mensaje es requerido';
        } elseif (mb_strlen(trim((string) $data['mensaje'])) < 10) {
            $errores['mensaje'] = 'El mensaje debe tener al menos 10 caracteres';
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