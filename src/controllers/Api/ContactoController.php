<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
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
    public function store(): void
    {
        $this->service->enviar(
            Request::json()
        );

        Response::created(
            [],
            'Mensaje enviado correctamente'
        );
    }
}