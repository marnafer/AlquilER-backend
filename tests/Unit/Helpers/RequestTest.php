<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Exceptions\BadRequestException;
use App\Helpers\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequestTest extends TestCase
{
    public function test_json_decodifica_un_objeto_json(): void
    {
        $this->assertSame(
            ['nombre' => 'Ana', 'edad' => 30],
            Request::json('{"nombre":"Ana","edad":30}')
        );
    }

    public function test_json_decodifica_un_arreglo_json(): void
    {
        $this->assertSame([1, 2, 3], Request::json('[1,2,3]'));
    }

    public function test_json_usa_el_body_inyectado_por_set_test_body(): void
    {
        Request::setTestBody('{"inyectado":true}');

        $this->assertSame(['inyectado' => true], Request::json());
    }

    public function test_el_body_inyectado_tiene_prioridad_sobre_el_parametro(): void
    {
        Request::setTestBody('{"inyectado":1}');

        $this->assertSame(
            ['inyectado' => 1],
            Request::json('{"porParametro":2}')
        );
    }

    public function test_json_lanza_si_el_cuerpo_esta_vacio(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'El cuerpo de la solicitud es obligatorio'
        );

        Request::json('');
    }

    public function test_json_lanza_si_el_cuerpo_solo_tiene_espacios(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'El cuerpo de la solicitud es obligatorio'
        );

        Request::json('    ');
    }

    public function test_json_lanza_si_el_json_es_invalido(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El JSON enviado no es válido');

        Request::json('{invalido');
    }

    #[DataProvider('jsonQueNoEsArreglo')]
    public function test_json_lanza_si_el_json_no_es_un_arreglo(
        string $json
    ): void {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El cuerpo debe ser un objeto JSON');

        Request::json($json);
    }

    public static function jsonQueNoEsArreglo(): array
    {
        return [
            'numero' => ['123'],
            'texto' => ['"hola"'],
            'booleano' => ['true'],
            'nulo' => ['null'],
        ];
    }
}
