<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\BadRequestException;
use JsonException;

class Request
{
<<<<<<< HEAD
    private static ?string $testBody = null;

    /**
     * Permite inyectar un body desde los tests.
     * En producción nadie llama a esto, así que $testBody queda null.
     */
    public static function setTestBody(?string $body): void
    {
        self::$testBody = $body;
    }

    public static function json(?string $body = null): array
    {
        if (self::$testBody !== null) {
            $body = self::$testBody;
        }

        $body ??= file_get_contents('php://input');
=======

    private static ?string $testBody = null;

    /** 
     * Permite establecer un body manualmente durante los tests. 
    */ 
    public static function setTestBody(?string $body): void 
    { 
        self::$testBody = $body; 
    }

    public static function json(): array
    {
        $body = self::$testBody ?? file_get_contents('php://input');
>>>>>>> 9e2c53299033f9f88a6195e662924ff4569b9be6

        if ($body === false || trim($body) === '') {
            throw new BadRequestException(
                'El cuerpo de la solicitud es obligatorio'
            );
        }

        try {
            $data = json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new BadRequestException(
                'El JSON enviado no es válido'
            );
        }

        if (!is_array($data)) {
            throw new BadRequestException(
                'El cuerpo debe ser un objeto JSON'
            );
        }

        return $data;
    }
}