<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\PropiedadSanitizer;
use PHPUnit\Framework\TestCase;

final class PropiedadSanitizerCoverageTest extends TestCase
{
    public function test_sanitizar_limit_devuelve_el_valor_si_no_es_numerico(): void
    {
        $metodo = new \ReflectionClass(PropiedadSanitizer::class);
        $metodo = $metodo->getMethod('sanitizarLimit');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke(null, 'abc');

        $this->assertSame('abc', $resultado);
    }
}