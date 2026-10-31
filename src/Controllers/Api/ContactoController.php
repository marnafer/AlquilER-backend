<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Services\ContactoService;

class ContactoController
{
    public function __construct(
        private readonly ContactoService $service
    ) {
    }

    /**
     * POST /api/contacto
     */
    public function store(Request $request): Response
    {
        $this->service->enviar(
            $request->json()
        );

        return Response::created(
            [],
            'Mensaje enviado correctamente'
        );
    }
}
