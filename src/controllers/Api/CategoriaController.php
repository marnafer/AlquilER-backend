<?php

namespace App\Controllers\Api;

use App\Sanitizers\CategoriaSanitizer;
use App\Validators\CategoriaValidator;
use App\Models\Categoria;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

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

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function obtener($id)
    {
        $idSan = CategoriaSanitizer::sanitizarIdCategoria($id);

        $validacion = CategoriaValidator::validarIdCategoria($idSan);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        try {

            $categoria = Categoria::find($idSan);

            if (!$categoria) {
                throw new NotFoundException('Categoría no encontrada');
            }

            Response::success($categoria);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function crear()
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $san = CategoriaSanitizer::sanitizarCategoria($raw);

        $validacion = CategoriaValidator::validarCategoria($san);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        try {

            if (Categoria::where('nombre', $san['nombre'])->exists()) {
                throw new BadRequestException(
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

        } catch (\Throwable $exception) {
            throw $exception;
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
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $categoria = Categoria::find($san['id']);

            if (!$categoria) {
                throw new NotFoundException('Categoría no encontrada');
            }

            if (
                Categoria::where('nombre', $san['nombre'])
                    ->where('id', '!=', $san['id'])
                    ->exists()
            ) {
                throw new BadRequestException(
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

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function eliminar($id)
    {
        $idSan = CategoriaSanitizer::sanitizarIdCategoria($id);

        $validacion = CategoriaValidator::validarIdCategoria($idSan);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        try {

            $categoria = Categoria::find($idSan);

            if (!$categoria) {
                throw new NotFoundException('Categoría no encontrada');
            }

            if ($categoria->propiedades()->exists()) {
                throw new BadRequestException(
                    'No se puede eliminar porque tiene propiedades asociadas'
                );
            }

            $categoria->delete();

            Response::success(
                [],
                200,
                'Categoría eliminada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}   