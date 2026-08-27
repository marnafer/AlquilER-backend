<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Services\CategoriaService;
use App\Middlewares\AutenticadorMiddleware;

class CategoriaController
{

    private readonly CategoriaService $service;

    public function __construct(CategoriaService $service)
    {
        $this->service = $service;
    }

    // GET /api/categorias
    public function index()
    {
        Response::success(
            $this->service->listar()
        );
    }

    // GET /api/categorias/{id}
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/categorias
    public function store()
    { 
        AutenticadorMiddleware::soloAdmin();

        $categoria = $this->service->crear(Request::json());

        Response::created(
            $categoria,
            'Categoría creada exitosamente'
        );
    }

    // PUT /api/categorias/{id}
    public function update($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar($id, Request::json());

        Response::success(
            [],
             200,
            'Categoría actualizada exitosamente'
         );

    }


    // DELETE /api/categorias/{id}
    public function delete($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->eliminar($id);

        Response::success(
            [],
            200,
            'Categoría eliminada exitosamente'
        );
    }

    // POST /api/categorias/{id}/restaurar

    public function restore($id)
    {
        AutenticadorMiddleware::soloAdmin();

       $this->service->restaurar($id);

       Response::success(
            [],
            200,
            'Categoría restaurada exitosamente'
        );
    }
}   