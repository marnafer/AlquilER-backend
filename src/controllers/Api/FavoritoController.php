<?php

namespace App\Controllers\Api;

use App\Models\Favorito;
use App\Sanitizers\FavoritoSanitizer;
use App\Validators\FavoritoValidator;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

class FavoritoController
{
    /**
     * GET /api/favoritos
     */
    public function index()
    {
        try {

            $favoritos = Favorito::with([
                'usuario',
                'propiedad'
            ])->get();

            Response::success([
                'items' => $favoritos,
                'total' => $favoritos->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/usuarios/{id}/favoritos
     */
    public function indexByUsuario(
        $usuarioId
    ) {

        $usuarioIdSan =
            FavoritoSanitizer::sanitizarUsuarioId(
                $usuarioId
            );

        $validacion =
            FavoritoValidator::validarUsuarioId(
                $usuarioIdSan
            );

        if (!$validacion['success']) {

            throw new ValidationException(
                [
                    'usuario_id' =>
                        $validacion['error']
                ]
            );
        }

        try {

            $favoritos = Favorito::where(
                'usuario_id',
                $usuarioIdSan
            )
            ->with('propiedad')
            ->get();

            Response::success([
                'items' => $favoritos,
                'total' => $favoritos->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/favoritos
     */
    public function store()
    {
        $raw = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($raw)) {
            throw new BadRequestException(
                'JSON inválido'
            );
        }

        $san =
            FavoritoSanitizer::sanitizarFavorito(
                $raw
            );

        $validacion =
            FavoritoValidator::validarCrearFavorito(
                $san
            );

        if (!$validacion['success']) {

            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $favorito = Favorito::create([
                'usuario_id' =>
                    $san['usuario_id'],

                'propiedad_id' =>
                    $san['propiedad_id']
            ]);

            Response::created(
                $favorito->toArray(),
                'Favorito creado exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/favoritos
     */
    public function delete()
    {
        $raw = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($raw)) {

            throw new BadRequestException(
                'JSON inválido'
            );
        }

        $san =
            FavoritoSanitizer::sanitizarFavorito(
                $raw
            );

        $validacion =
            FavoritoValidator::validarEliminarFavorito(
                $san
            );

        if (!$validacion['success']) {

            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $favorito = Favorito::where(
                'usuario_id',
                $san['usuario_id']
            )
            ->where(
                'propiedad_id',
                $san['propiedad_id']
            )
            ->first();

            $favorito->delete();

            Response::success([
                'message' => 'Favorito eliminado exitosamente'
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/favoritos/{id}
     */
    public function deleteById(
        $id
    ) {

        $idSan =
            FavoritoSanitizer::sanitizarIdFavorito(
                $id
            );

        $validacion =
            FavoritoValidator::validarSoloIdFavorito(
                $idSan
            );

        if (!$validacion['success']) {

            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $favorito =
                Favorito::find(
                    $idSan
                );

            if (!$favorito) {

                throw new NotFoundException(
                    'Favorito no encontrado'
                );
            }

            $favorito->delete();

            Response::success([
                'message' => 'Favorito eliminado exitosamente'
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}