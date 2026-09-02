<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadImagenService;

class PropiedadImagenController
{
    private readonly PropiedadImagenService $service;

    public function __construct(PropiedadImagenService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

        if ($queryString !== '') {
            $aceptado = preg_match('/^id=\d+$/', $queryString) === 1
                || preg_match('/^$/', $queryString) === 1;

            if (!$aceptado) {
                throw new \App\Exceptions\ValidationException([
                    'id' => 'Parámetro de consulta inválido. Use ?id=123'
                ]);
            }
        }

        Response::success(
            $this->service->listar($_GET['id'] ?? null)
        );
    }

    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    public function store()
    {
        $user = AutenticadorMiddleware::verificar();

        $raw = $_POST;
        $file = $_FILES['imagen'] ?? null;

        $imagen = $this->service->crear($raw, $file, $user);

        Response::created(
            $imagen,
            'Imagen guardada correctamente'
        );
    }

    public function setPrincipal($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $imagen = $this->service->establecerPrincipal(
            (int) $id,
            $user
        );

        Response::success(
            $imagen,
            200,
            'Imagen principal actualizada'
        );
    }

    public function delete($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            (int) $id,
            $user
        );

        Response::success(
            [],
            200,
            'Imagen eliminada correctamente'
        );
    }
}