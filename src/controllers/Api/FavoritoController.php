<?php

namespace App\Controllers\Api;

use App\Models\Favorito;
use App\Sanitizers\FavoritoSanitizer;
use App\Validators\FavoritoValidator;
use App\Helpers\Response;

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

        } catch (\Exception $e) {

            Response::serverError();
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

            Response::validationError(
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

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::badRequest(
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

            Response::validationError(
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

        } catch (\Exception $e) {

            Response::serverError();
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

            Response::badRequest(
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

            Response::validationError(
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

        } catch (\Exception $e) {

            Response::serverError();
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

            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $favorito =
                Favorito::find(
                    $idSan
                );

            if (!$favorito) {

                Response::notFound(
                    'Favorito no encontrado'
                );
            }

            $favorito->delete();

            Response::success([
                'message' => 'Favorito eliminado exitosamente'
            ]);

        } catch (\Exception $e) {

            Response::serverError();
        }
    }
}