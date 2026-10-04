<?php

namespace App\Sanitizers;

class MensajeConsultaSanitizer
{
    /**
     * Sanitizar mensaje completo
     */
    public static function sanitizarMensajeConsulta(
        array $data
    ): array {

        return [
            'id' => self::sanitizarEntero(
                $data['id'] ?? null
            ),
            'consulta_id' => self::sanitizarEntero(
                $data['consulta_id'] ?? null
            ),
            'usuario_id' => self::sanitizarEntero(
                $data['usuario_id'] ?? null
            ),
            'mensaje' => self::sanitizarMensajeTexto(
                $data['mensaje'] ?? null
            )
        ];
    }

    /**
     * Sanitizar mensaje de texto
     * (Evitamos aplanar los saltos de línea para conservar el formato del chat)
     */
    public static function sanitizarMensajeTexto(
        $mensaje
    ): ?string {

        if ($mensaje === null || $mensaje === '') {
            return null;
        }

        $mensaje = trim($mensaje);
        $mensaje = strip_tags($mensaje);
        $mensaje = htmlspecialchars(
            $mensaje,
            ENT_QUOTES,
            'UTF-8'
        );

        return $mensaje;
    }

    /**
     * Sanitizar entero positivo
     */
    public static function sanitizarEntero(
        $valor
    ): ?int {

        if ($valor === null || $valor === '') {
            return null;
        }

        $valorSanitizado = filter_var(
            $valor,
            FILTER_VALIDATE_INT
        );

        return (
            $valorSanitizado !== false &&
            $valorSanitizado > 0
        )
            ? $valorSanitizado
            : null;
    }
}