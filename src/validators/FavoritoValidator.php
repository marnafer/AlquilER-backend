<?php

namespace App\Validators;

use App\Models\Favorito;
use App\Models\Usuario;
use App\Models\Propiedad;

class FavoritoValidator
{
    public static function validarFavorito(
        $data
    ): array {

        $errores = [];

        $resultado = self::validarUsuarioId(
            $data['usuario_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['usuario_id'] = $resultado['error'];
        }

        $resultado = self::validarPropiedadId(
            $data['propiedad_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['propiedad_id'] = $resultado['error'];
        }

        if (empty($errores)) {

            $existe = Favorito::where(
                'usuario_id',
                $data['usuario_id']
            )
            ->where(
                'propiedad_id',
                $data['propiedad_id']
            )
            ->exists();

            if ($existe) {

                $errores['favorito'] =
                    'La propiedad ya se encuentra en favoritos';
            }
        }

        if (!empty($errores)) {

            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errores
            ];
        }

        return [
            'success' => true,
            'message' => 'Validación exitosa',
            'errors' => null
        ];
    }

    public static function validarEliminarFavorito(
        $data
    ): array {

        $errores = [];

        $resultado = self::validarUsuarioId(
            $data['usuario_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['usuario_id'] = $resultado['error'];
        }

        $resultado = self::validarPropiedadId(
            $data['propiedad_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['propiedad_id'] = $resultado['error'];
        }

        if (empty($errores)) {

            $favorito = Favorito::where(
                'usuario_id',
                $data['usuario_id']
            )
            ->where(
                'propiedad_id',
                $data['propiedad_id']
            )
            ->first();

            if (!$favorito) {

                $errores['favorito'] =
                    'El favorito no existe';
            }
        }

        if (!empty($errores)) {

            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errores
            ];
        }

        return [
            'success' => true,
            'message' => 'Validación exitosa',
            'errors' => null
        ];
    }

    public static function validarIdFavorito(
        $id
    ): array {

        return self::validarIdRequerido(
            $id,
            'favorito'
        );
    }

    public static function validarSoloIdFavorito(
        $id
    ): array {

        $resultado = self::validarIdFavorito(
            $id
        );

        if (!$resultado['success']) {

            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => [
                    'id' => $resultado['error']
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'ID válido',
            'errors' => null
        ];
    }

    public static function validarIdRequerido(
        $id,
        $campo = ''
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo es requerido. Debe ser un entero positivo"
            ];
        }

        if (
            !is_numeric($id) ||
            $id <= 0
        ) {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo debe ser positivo"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarUsuarioId(
        $usuarioId
    ): array {

        if (
            $usuarioId === null ||
            $usuarioId === ''
        ) {

            return [
                'success' => false,
                'error' =>
                    'El usuario es requerido. El ID debe ser un entero positivo'
            ];
        }

        if (
            !is_numeric($usuarioId) ||
            $usuarioId <= 0
        ) {

            return [
                'success' => false,
                'error' =>
                    'Usuario inválido'
            ];
        }

        if (!Usuario::find($usuarioId)) {

            return [
                'success' => false,
                'error' =>
                    'El usuario no existe'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarPropiedadId(
        $propiedadId
    ): array {

        if (
            $propiedadId === null ||
            $propiedadId === ''
        ) {

            return [
                'success' => false,
                'error' =>
                    'La propiedad es requerida. El ID debe ser un entero positivo'
            ];
        }

        if (
            !is_numeric($propiedadId) ||
            $propiedadId <= 0
        ) {

            return [
                'success' => false,
                'error' =>
                    'Propiedad inválida'
            ];
        }

        if (!Propiedad::find($propiedadId)) {

            return [
                'success' => false,
                'error' =>
                    'La propiedad no existe'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarCrearFavorito(
        $data
    ): array {

        return self::validarFavorito(
            $data
        );
    }
}