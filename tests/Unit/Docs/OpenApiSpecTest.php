<?php
declare(strict_types=1);

namespace Tests\Unit\Docs;

use App\Docs\AutenticadorDocs;
use App\Docs\OpenApiSpec;
use App\Docs\Schemas\AutenticadorSchemas;
use PHPUnit\Framework\TestCase;

final class OpenApiSpecTest extends TestCase
{
    public function test_instancia_las_clases_docs_e_invoca_los_stubs(): void
    {
        $openApiSpec = new OpenApiSpec();
        $openApiSpec->health();

        $autenticadorDocs = new AutenticadorDocs();
        $autenticadorDocs->login();
        $autenticadorDocs->register();
        $autenticadorDocs->logout();
        $autenticadorDocs->refresh();
        $autenticadorDocs->solicitarRecuperacion();
        $autenticadorDocs->restablecerContrasena();

        $autenticadorSchemas = new AutenticadorSchemas();

        $this->assertInstanceOf(OpenApiSpec::class, $openApiSpec);
        $this->assertInstanceOf(AutenticadorDocs::class, $autenticadorDocs);
        $this->assertInstanceOf(AutenticadorSchemas::class, $autenticadorSchemas);
    }
}
