<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\ContactoSanitizer;
use Tests\TestCase;

class ContactoSanitizerTest extends TestCase
{
    public function test_sanitizar_full_payload(): void
    {
        $resultado = ContactoSanitizer::sanitizar([
            'nombre' => '  Juan   Perez ',
            'email' => '  Juan@Example.com ',
            'asunto' => '  Consulta   general ',
            'mensaje' => "  Hola\t\tmundo  ",
        ]);

        $this->assertSame('Juan Perez', $resultado['nombre']);
        $this->assertSame('juan@example.com', $resultado['email']);
        $this->assertSame('Consulta general', $resultado['asunto']);
        $this->assertSame('Hola mundo', $resultado['mensaje']);
    }

    public function test_sanitizar_empty_payload_returns_nulls(): void
    {
        $resultado = ContactoSanitizer::sanitizar([]);

        $this->assertNull($resultado['nombre']);
        $this->assertNull($resultado['email']);
        $this->assertNull($resultado['asunto']);
        $this->assertNull($resultado['mensaje']);
    }

    public function test_sanitizar_blank_values_return_null(): void
    {
        $resultado = ContactoSanitizer::sanitizar([
            'nombre' => '   ',
            'asunto' => '   ',
            'mensaje' => '   ',
        ]);

        $this->assertNull($resultado['nombre']);
        $this->assertNull($resultado['asunto']);
        $this->assertNull($resultado['mensaje']);
    }

    public function test_sanitizar_truncates_fields(): void
    {
        $resultado = ContactoSanitizer::sanitizar([
            'nombre' => str_repeat('a', 120),
            'asunto' => str_repeat('b', 200),
            'mensaje' => str_repeat('c', 6000),
        ]);

        $this->assertSame(100, mb_strlen($resultado['nombre']));
        $this->assertSame(150, mb_strlen($resultado['asunto']));
        $this->assertSame(5000, mb_strlen($resultado['mensaje']));
    }
}
