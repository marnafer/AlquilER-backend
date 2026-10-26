<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ProfanityFilter;
use PHPUnit\Framework\TestCase;

final class ProfanityFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        // Resetear cache invalidando terminos agregados: re-agregar nada; el cache se mantiene.
        // Forzamos invalidacion agregando y quitando no es posible; los tests de agregarTerminos
        // se ejecutan al final y no afectan otros porque usan terminos nuevos unicos.
        parent::tearDown();
    }

    public function test_normalizar_minusculas_y_sin_acentos(): void
    {
        $this->assertSame('puta', ProfanityFilter::normalizar('PÚTA'));
        $this->assertSame('mierda', ProfanityFilter::normalizar('Mïérda'));
    }

    public function test_normalizar_elimina_separadores(): void
    {
        $this->assertSame('puta', ProfanityFilter::normalizar('p.u.t.a'));
        $this->assertSame('puta', ProfanityFilter::normalizar('p u t a'));
    }

    public function test_normalizar_leetspeak(): void
    {
        $this->assertSame('mierda', ProfanityFilter::normalizar('m13rd4'));
    }

    public function test_normalizar_texto_vacio(): void
    {
        $this->assertSame('', ProfanityFilter::normalizar(''));
        $this->assertSame('', ProfanityFilter::normalizar('     '));
    }

    public function test_contiene_false_para_texto_limpio(): void
    {
        $this->assertFalse(ProfanityFilter::contiene('Excelente lugar, muy limpio y comodo'));
    }

    public function test_contiene_false_para_texto_vacio(): void
    {
        $this->assertFalse(ProfanityFilter::contiene(''));
        $this->assertFalse(ProfanityFilter::contiene('   '));
    }

    public function test_contiene_true_para_groseria_directa(): void
    {
        $this->assertTrue(ProfanityFilter::contiene('Esto es una puta mierda'));
    }

    public function test_contiene_true_para_mayusculas(): void
    {
        $this->assertTrue(ProfanityFilter::contiene('PUTA'));
    }

    public function test_contiene_true_para_leetspeak(): void
    {
        $this->assertTrue(ProfanityFilter::contiene('m13rd4'));
    }

    public function test_contiene_true_para_separadores(): void
    {
        $this->assertTrue(ProfanityFilter::contiene('p.u.t.a'));
    }

    public function test_contiene_true_para_ingles(): void
    {
        $this->assertTrue(ProfanityFilter::contiene('what a fuck'));
    }

    public function test_enmascarar_texto_limpio_devuelve_original(): void
    {
        $texto = 'Hola mundo';
        $this->assertSame($texto, ProfanityFilter::enmascarar($texto));
    }

    public function test_enmascarar_texto_sucio_devuelve_marcador(): void
    {
        $this->assertSame('[contenido inapropiado]', ProfanityFilter::enmascarar('puta'));
    }

    public function test_agregarTerminos_agrega_y_detecta(): void
    {
        ProfanityFilter::agregarTerminos(['palabranueva']);
        $this->assertTrue(ProfanityFilter::contiene('esto tiene palabranueva'));
    }

    public function test_agregarTerminos_ignora_vacios_y_no_strings(): void
    {
        ProfanityFilter::agregarTerminos(['', '   ', 123, null]);
        // no debe romper; termino existente sigue detectandose
        $this->assertTrue(ProfanityFilter::contiene('puta'));
    }

    public function test_cache_se_reutiliza(): void
    {
        // Segunda llamada debe usar cache y seguir funcionando
        $this->assertTrue(ProfanityFilter::contiene('mierda'));
        $this->assertFalse(ProfanityFilter::contiene('limpio'));
    }
}
