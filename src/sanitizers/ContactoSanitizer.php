<?php

declare(strict_types=1);

namespace App\Sanitizers;

class ContactoSanitizer
{
    public static function sanitizar(array $data): array
    {
        return [
            'nombre' => self::sanitizarTexto(
                $data['nombre'] ?? null,
                100
            ),
            'email' => UsuarioSanitizer::sanitizarEmail(
                $data['email'] ?? null
            ),
            'asunto' => self::sanitizarTexto(
                $data['asunto'] ?? null,
                150
            ),
            'mensaje' => self::sanitizarMensaje(
                $data['mensaje'] ?? null
            ),
        ];
    }

    private static function sanitizarTexto(
        $valor,
        int $max
    ): ?string {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $texto = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $valor)
        );

        return mb_substr($texto, 0, $max);
    }

    private static function sanitizarMensaje(
        $valor
    ): ?string {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $mensaje = preg_replace(
            '/[ \t]+/u',
            ' ',
            trim((string) $valor)
        );

        return mb_substr($mensaje, 0, 5000);
    }
}