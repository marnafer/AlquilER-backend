<?php

namespace App\Sanitizers;

class FavoritoSanitizer
{
    public static function sanitizarFavorito(
        $data
    ): array {

        return [
            'id' => self::sanitizarIdFavorito(
                $data['id'] ?? null
            ),

            'usuario_id' => self::sanitizarUsuarioId(
                $data['usuario_id'] ?? null
            ),

            'propiedad_id' => self::sanitizarPropiedadId(
                $data['propiedad_id'] ?? null
            )
        ];
    }

    public static function sanitizarIdFavorito(
        $id
    ) {

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

    public static function sanitizarUsuarioId(
        $usuarioId
    ) {

        if (
            $usuarioId === null ||
            $usuarioId === ''
        ) {
            return null;
        }

        $usuarioId = filter_var(
            $usuarioId,
            FILTER_VALIDATE_INT
        );

        return (
            $usuarioId !== false &&
            $usuarioId > 0
        )
            ? $usuarioId
            : null;
    }

    public static function sanitizarPropiedadId(
        $propiedadId
    ) {

        if (
            $propiedadId === null ||
            $propiedadId === ''
        ) {
            return null;
        }

        $propiedadId = filter_var(
            $propiedadId,
            FILTER_VALIDATE_INT
        );

        return (
            $propiedadId !== false &&
            $propiedadId > 0
        )
            ? $propiedadId
            : null;
    }
}