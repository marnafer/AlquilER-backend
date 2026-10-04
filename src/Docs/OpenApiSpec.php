<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'AlquilER API',
        version: '1.0.0',
        description: 'API REST del sistema AlquilER'
    ),
    servers: [
        new OA\Server(
            url: 'http://localhost:8000',
            description: 'Servidor local'
        )
    ],
    components: new OA\Components(
    securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: 'bearerAuth',
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT'
        )
    ]
)
)]
final class OpenApiSpec
{
    #[OA\Get(
        path: '/api/health',
        responses: [
            new OA\Response(
                response: 200,
                description: 'API funcionando correctamente'
            )
        ]
    )]
    public function health(): void
    {
    }
}


