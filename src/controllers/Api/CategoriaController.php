<?php

namespace App\Controllers\Api;

use App\Sanitizers\CategoriaSanitizer;
use App\Validators\CategoriaValidator;
use App\Models\Categoria;
use App\Helpers\Response;

class CategoriaController
{
    public function listar()
    {
        try {

            $categorias = Categoria::all();

            Response::success([
                'items' => $categorias,
                'total' => $categorias->count()
            ]);

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }

    public function obtener($id)
    {
        $idSan = CategoriaSanitizer::sanitizarIdCategoria($id);

        $validacion = CategoriaValidator::validarIdCategoria($idSan);

        if (!$validacion['success']) {
            Response::validationError([
                'id' => [$validacion['error']]
            ]);
        }

        try {

            $categoria = Categoria::find($idSan);

            if (!$categoria) {
                Response::notFound('Categoría no encontrada');
            }

            Response::success($categoria);

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }

    public function crear()
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $san = CategoriaSanitizer::sanitizarCategoria($raw);

        $validacion = CategoriaValidator::validarCategoria($san);

        if (!$validacion['success']) {
            Response::validationError($validacion['errors']);
        }

        try {

            if (Categoria::where('nombre', $san['nombre'])->exists()) {
                Response::badRequest(
                    'Ya existe una categoría con este nombre'
                );
            }

            $categoria = Categoria::create([
                'nombre' => $san['nombre']
            ]);

            Response::created(
                $categoria,
                'Categoría creada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }

    public function actualizar($id)
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $raw['id'] = $id;

        $san = CategoriaSanitizer::sanitizarCategoria($raw);

        $validacion = CategoriaValidator::validarCategoria(
            $san,
            true
        );

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $categoria = Categoria::find($san['id']);

            if (!$categoria) {
                Response::notFound('Categoría no encontrada');
            }

            if (
                Categoria::where('nombre', $san['nombre'])
                    ->where('id', '!=', $san['id'])
                    ->exists()
            ) {
                Response::badRequest(
                    'Ya existe otra categoría con este nombre'
                );
            }

            $categoria->update([
                'nombre' => $san['nombre']
            ]);

            Response::success(
                $categoria,
                200,
                'Categoría actualizada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }

    public function eliminar($id)
    {
        $idSan = CategoriaSanitizer::sanitizarIdCategoria($id);

        $validacion = CategoriaValidator::validarIdCategoria($idSan);

        if (!$validacion['success']) {
            Response::validationError([
                'id' => [$validacion['error']]
            ]);
        }

        try {

            $categoria = Categoria::find($idSan);

            if (!$categoria) {
                Response::notFound('Categoría no encontrada');
            }

            if ($categoria->propiedades()->exists()) {
                Response::badRequest(
                    'No se puede eliminar porque tiene propiedades asociadas'
                );
            }

            $categoria->delete();

            Response::success(
                [],
                200,
                'Categoría eliminada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }
}   