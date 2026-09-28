<?php

namespace App\Sanitizers;

class ResenaSanitizer
{
    /**
     * Sanitizar datos para crear reseña
     */
    public static function sanitizarCrear(array $data): array
    {
        return [
            'reserva_id' => self::sanitizarId(
                $data['reserva_id'] ?? null
            ),

            'calificacion' => self::sanitizarCalificacion(
                $data['calificacion'] ?? null
            ),

            'comentario' => self::sanitizarComentario(
                $data['comentario'] ?? null
            )
        ];
    }

    /**
     * Sanitizar datos para actualizar reseña
     */
    public static function sanitizarActualizar(array $data): array
    {
        return [
            'id' => self::sanitizarId(
                $data['id'] ?? null
            ),

            'calificacion' => self::sanitizarCalificacion(
                $data['calificacion'] ?? null
            ),

            'comentario' => self::sanitizarComentario(
                $data['comentario'] ?? null
            )
        ];
    }

    /**
     * Sanitizar ID
     */
    public static function sanitizarId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        return (
            $id !== false &&
            $id > 0
        )
            ? $id
            : null;
    }

    /**
     * Sanitizar calificación
     */
    public static function sanitizarCalificacion(
        $calificacion
    ): ?int {
        if (
            $calificacion === null ||
            $calificacion === ''
        ) {
            return null;
        }

        $calificacion = filter_var(
            $calificacion,
            FILTER_VALIDATE_INT
        );

        return (
            $calificacion !== false
        )
            ? (int)$calificacion
            : null;
    }

    /**
     * Sanitizar comentario
     */
    public static function sanitizarComentario(
        $comentario
    ): ?string {
        if (
            $comentario === null ||
            trim($comentario) === ''
        ) {
            return null;
        }

        $comentario = trim($comentario);

        $comentario = preg_replace(
            '/\s+/u',
            ' ',
            $comentario
        );

        $comentario = strip_tags(
            $comentario
        );

        return htmlspecialchars(
            $comentario,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}