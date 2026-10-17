<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ConsultaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsultaValidatorTest extends TestCase
{
    #[DataProvider('idsNumericosValidos')]
    public function test_validar_id_requerido_ok(mixed $id): void
    {
        $resultado = ConsultaValidator::validarIdRequerido($id, 'consulta');

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function idsNumericosValidos(): array
    {
        return [
            'entero' => [9],
            'cadena_numerica' => ['9'],
        ];
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_id_requerido_falla(mixed $id): void
    {
        $resultado = ConsultaValidator::validarIdRequerido($id, 'consulta');

        $this->assertFalse($resultado['success']);
        $this->assertSame('El ID de consulta debe ser positivo', $resultado['error']);
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [null],
            'vacio' => [''],
            'texto' => ['abc'],
            'cero' => [0],
            'negativo' => [-5],
            'decimal' => [2.5],
        ];
    }

    public function test_validar_id_requerido_sin_campo_usa_el_nombre_vacio(): void
    {
        $resultado = ConsultaValidator::validarIdRequerido(null);

        $this->assertFalse($resultado['success']);
        $this->assertSame('El ID de  debe ser positivo', $resultado['error']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_propiedad_id_falla(mixed $id): void
    {
        $resultado = ConsultaValidator::validarPropiedadId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('El ID de propiedad debe ser positivo', $resultado['error']);
    }

    #[DataProvider('idsNumericosValidos')]
    public function test_validar_propiedad_id_ok(mixed $id): void
    {
        $resultado = ConsultaValidator::validarPropiedadId($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_usuario_id_falla(mixed $id): void
    {
        $resultado = ConsultaValidator::validarUsuarioId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'El ID de usuario debe ser un entero positivo',
            $resultado['error']
        );
    }

    #[DataProvider('idsNumericosValidos')]
    public function test_validar_usuario_id_ok(mixed $id): void
    {
        $resultado = ConsultaValidator::validarUsuarioId($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('mensajesInvalidos')]
    public function test_validar_mensaje_consulta_falla(
        mixed $mensaje,
        string $errorEsperado
    ): void {
        $resultado = ConsultaValidator::validarMensajeConsulta($mensaje);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function mensajesInvalidos(): array
    {
        return [
            'null' => [null, 'El mensaje es requerido'],
            'vacio' => ['', 'El mensaje es requerido'],
            'muy_corto' => [
                'abcd',
                'El mensaje debe tener al menos 5 caracteres',
            ],
            'con_espacios_solo' => [
                '  ab  ',
                'El mensaje debe tener al menos 5 caracteres',
            ],
            'muy_largo' => [
                str_repeat('a', 5001),
                'El mensaje no puede superar los 5000 caracteres',
            ],
        ];
    }

    public function test_validar_mensaje_consulta_ok(): void
    {
        $resultado = ConsultaValidator::validarMensajeConsulta(
            '  Hola, quería consultar disponibilidad.  '
        );

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('fechasInvalidas')]
    public function test_validar_fecha_consulta_invalida_falla(
        string $fecha
    ): void {
        $resultado = ConsultaValidator::validarFechaConsulta($fecha);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Fecha inválida', $resultado['error']);
    }

    public static function fechasInvalidas(): array
    {
        return [
            'texto' => ['no-es-fecha'],
            'vacia' => [''],
        ];
    }

    public function test_validar_fecha_consulta_futura_falla(): void
    {
        $resultado = ConsultaValidator::validarFechaConsulta(
            date('Y-m-d', strtotime('+1 day'))
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('La fecha no puede ser futura', $resultado['error']);
    }

    public function test_validar_fecha_consulta_pasada_ok(): void
    {
        $resultado = ConsultaValidator::validarFechaConsulta('2020-03-15');

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public function test_validar_consulta_ok(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
            'usuario_id' => 2,
            'mensaje' => 'Hola, quería consultar disponibilidad',
            'fecha_consulta' => '2020-03-15',
            'perfil_interesado' => null,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_consulta_sin_campos_opcionales_es_ok(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_consulta_requiriendo_id_sin_id_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta(
            ['propiedad_id' => 1],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de consulta debe ser positivo'],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_sin_propiedad_id_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['propiedad_id' => 'El ID de propiedad debe ser positivo'],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_con_usuario_id_invalido_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
            'usuario_id' => 0,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['usuario_id' => 'El ID de usuario debe ser un entero positivo'],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_con_mensaje_invalido_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
            'mensaje' => 'abc',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['mensaje' => 'El mensaje debe tener al menos 5 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_con_fecha_invalida_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
            'fecha_consulta' => 'ayer',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['fecha_consulta' => 'Fecha inválida'],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_con_fecha_futura_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
            'fecha_consulta' => date('Y-m-d', strtotime('+3 days')),
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['fecha_consulta' => 'La fecha no puede ser futura'],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_con_perfil_que_no_es_objeto_falla(): void
    {
        $resultado = ConsultaValidator::validarConsulta([
            'propiedad_id' => 1,
            'perfil_interesado' => 'no-objeto',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['perfil_interesado' => ['El perfil del interesado debe ser un objeto']],
            $resultado['errors']
        );
    }

    public function test_validar_consulta_con_varios_errores_acumula_todos(): void
    {
        $resultado = ConsultaValidator::validarConsulta([], true);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' => 'El ID de consulta debe ser positivo',
                'propiedad_id' => 'El ID de propiedad debe ser positivo',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_crear_consulta_ok(): void
    {
        $resultado = ConsultaValidator::validarCrearConsulta([
            'propiedad_id' => 1,
            'usuario_id' => 2,
            'mensaje' => 'Hola, quería consultar disponibilidad',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_consulta_con_perfil_valido_ok(): void
    {
        $resultado = ConsultaValidator::validarCrearConsulta([
            'propiedad_id' => 1,
            'mensaje' => 'Hola, quería consultar disponibilidad',
            'perfil_interesado' => $this->perfilValido(),
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_consulta_con_perfil_invalido_falla(): void
    {
        $resultado = ConsultaValidator::validarCrearConsulta([
            'propiedad_id' => 1,
            'mensaje' => 'Hola, quería consultar disponibilidad',
            'perfil_interesado' => 'no-objeto',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['perfil_interesado' => ['El perfil del interesado debe ser un objeto']],
            $resultado['errors']
        );
    }

    public function test_validar_crear_consulta_con_datos_y_perfil_invalidos_acumula_errores(): void
    {
        $resultado = ConsultaValidator::validarCrearConsulta([
            'propiedad_id' => null,
            'mensaje' => 'abc',
            'perfil_interesado' => ['cantidad_ocupantes' => 999],
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertArrayHasKey('propiedad_id', $resultado['errors']);
        $this->assertArrayHasKey('mensaje', $resultado['errors']);
        $this->assertArrayHasKey('perfil_interesado', $resultado['errors']);
        $this->assertSame(
            'Debe ser un entero entre 1 y 50',
            $resultado['errors']['perfil_interesado']['cantidad_ocupantes']
        );
    }

    public function test_validar_actualizar_consulta_ok(): void
    {
        $resultado = ConsultaValidator::validarActualizarConsulta([
            'id' => 10,
            'mensaje' => 'Consulta actualizada con éxito',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_actualizar_consulta_sin_id_falla(): void
    {
        $resultado = ConsultaValidator::validarActualizarConsulta([
            'mensaje' => 'Consulta actualizada con éxito',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de consulta debe ser positivo'],
            $resultado['errors']
        );
    }

    public function test_validar_actualizar_consulta_sin_mensaje_falla(): void
    {
        $resultado = ConsultaValidator::validarActualizarConsulta([
            'id' => 10,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['mensaje' => 'El mensaje es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_actualizar_consulta_con_ambos_campos_invalidos_acumula_errores(): void
    {
        $resultado = ConsultaValidator::validarActualizarConsulta([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'id' => 'El ID de consulta debe ser positivo',
                'mensaje' => 'El mensaje es requerido',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_consulta_ok(): void
    {
        $resultado = ConsultaValidator::validarSoloIdConsulta(3);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_consulta_falla(mixed $id): void
    {
        $resultado = ConsultaValidator::validarSoloIdConsulta($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de consulta debe ser positivo'],
            $resultado['errors']
        );
    }

    public function test_validar_perfil_interesado_null_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], ConsultaValidator::validarPerfilInteresado(null));
    }

    public function test_validar_perfil_interesado_que_no_es_objeto_devuelve_error(): void
    {
        $this->assertSame(
            ['El perfil del interesado debe ser un objeto'],
            ConsultaValidator::validarPerfilInteresado('no-objeto')
        );
    }

    public function test_validar_perfil_interesado_valido_devuelve_arreglo_vacio(): void
    {
        $this->assertSame(
            [],
            ConsultaValidator::validarPerfilInteresado($this->perfilValido())
        );
    }

    public function test_validar_perfil_interesado_sin_fecha_de_mudanza_falla(): void
    {
        $perfil = $this->perfilValido();
        unset($perfil['fecha_mudanza']);

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'Debe ser una fecha válida en formato YYYY-MM-DD',
            $errores['fecha_mudanza']
        );
    }

    #[DataProvider('fechasDeMudanzaInvalidas')]
    public function test_validar_perfil_interesado_con_fecha_invalida_falla(
        mixed $fecha
    ): void {
        $perfil = $this->perfilValido();
        $perfil['fecha_mudanza'] = $fecha;

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'Debe ser una fecha válida en formato YYYY-MM-DD',
            $errores['fecha_mudanza']
        );
    }

    public static function fechasDeMudanzaInvalidas(): array
    {
        return [
            'texto' => ['no-es-fecha'],
            'sin_formato' => ['2026-1-1'],
            'entero' => [20260101],
            'dia_inexistente' => ['2026-02-31'],
        ];
    }

    public function test_validar_perfil_interesado_con_fecha_anterior_a_hoy_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['fecha_mudanza'] = '2020-01-01';

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'La fecha de mudanza no puede ser anterior a hoy',
            $errores['fecha_mudanza']
        );
    }

    public function test_validar_perfil_interesado_con_fecha_de_hoy_es_valido(): void
    {
        $perfil = $this->perfilValido();
        $perfil['fecha_mudanza'] = date('Y-m-d');

        $this->assertSame(
            [],
            ConsultaValidator::validarPerfilInteresado($perfil)
        );
    }

    #[DataProvider('ocupantesInvalidos')]
    public function test_validar_perfil_interesado_con_ocupantes_invalidos_falla(
        mixed $ocupantes
    ): void {
        $perfil = $this->perfilValido();
        $perfil['cantidad_ocupantes'] = $ocupantes;

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'Debe ser un entero entre 1 y 50',
            $errores['cantidad_ocupantes']
        );
    }

    public static function ocupantesInvalidos(): array
    {
        return [
            'ausente' => [null],
            'cero' => [0],
            'cincuenta_y_uno' => [51],
            'texto' => ['varios'],
            'decimal' => [2.5],
        ];
    }

    #[DataProvider('ocupantesValidos')]
    public function test_validar_perfil_interesado_con_ocupantes_validos_ok(
        int $ocupantes
    ): void {
        $perfil = $this->perfilValido();
        $perfil['cantidad_ocupantes'] = $ocupantes;

        $this->assertSame(
            [],
            ConsultaValidator::validarPerfilInteresado($perfil)
        );
    }

    public static function ocupantesValidos(): array
    {
        return [
            'minimo' => [1],
            'maximo' => [50],
            'cadena_numerica' => [5],
        ];
    }

    public function test_validar_perfil_interesado_sin_indicacion_de_mascotas_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['tiene_mascotas'] = 'quizas';

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'Debe indicar si tiene mascotas',
            $errores['tiene_mascotas']
        );
    }

    #[DataProvider('cantidadesDeMascotasInvalidas')]
    public function test_validar_perfil_interesado_con_cantidad_de_mascotas_invalida_falla(
        mixed $cantidadMascotas
    ): void {
        $perfil = $this->perfilValido();
        $perfil['cantidad_mascotas'] = $cantidadMascotas;

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'Debe ser un entero entre 0 y 20',
            $errores['cantidad_mascotas']
        );
    }

    public static function cantidadesDeMascotasInvalidas(): array
    {
        return [
            'ausente' => [null],
            'negativa' => [-1],
            'veintiuno' => [21],
            'texto' => ['dos'],
        ];
    }

    public function test_validar_perfil_interesado_sin_mascotas_y_cantidad_mayor_cero_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['tiene_mascotas'] = false;
        $perfil['cantidad_mascotas'] = 2;

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'La cantidad debe coincidir con la información sobre mascotas',
            $errores['cantidad_mascotas']
        );
    }

    public function test_validar_perfil_interesado_con_mascotas_y_cantidad_cero_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['tiene_mascotas'] = true;
        $perfil['cantidad_mascotas'] = 0;

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'La cantidad debe coincidir con la información sobre mascotas',
            $errores['cantidad_mascotas']
        );
    }

    public function test_validar_perfil_interesado_con_cantidad_de_mascotas_acorde_es_valido(): void
    {
        $perfil = $this->perfilValido();
        $perfil['tiene_mascotas'] = false;
        $perfil['cantidad_mascotas'] = 0;

        $this->assertSame(
            [],
            ConsultaValidator::validarPerfilInteresado($perfil)
        );
    }

    public function test_validar_perfil_interesado_sin_lista_de_garantias_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['garantias'] = 'garante';

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'Debe enviar una lista de garantías',
            $errores['garantias']
        );
    }

    public function test_validar_perfil_interesado_con_garantia_no_permitida_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['garantias'] = ['recibo_sueldo', 'prenda_de_oro'];

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'La lista contiene una garantía no permitida',
            $errores['garantias']
        );
    }

    public function test_validar_perfil_interesado_con_garantia_que_no_es_texto_falla(): void
    {
        $perfil = $this->perfilValido();
        $perfil['garantias'] = ['garante', 123];

        $errores = ConsultaValidator::validarPerfilInteresado($perfil);

        $this->assertSame(
            'La lista contiene una garantía no permitida',
            $errores['garantias']
        );
    }

    public function test_validar_perfil_interesado_vacio_acumula_todos_los_errores(): void
    {
        $errores = ConsultaValidator::validarPerfilInteresado([]);

        $this->assertArrayHasKey('fecha_mudanza', $errores);
        $this->assertArrayHasKey('cantidad_ocupantes', $errores);
        $this->assertArrayHasKey('cantidad_mascotas', $errores);
        $this->assertArrayHasKey('garantias', $errores);
        $this->assertCount(4, $errores);
    }

    private function perfilValido(): array
    {
        return [
            'fecha_mudanza' => date('Y-m-d', strtotime('+7 days')),
            'cantidad_ocupantes' => 2,
            'tiene_mascotas' => true,
            'cantidad_mascotas' => 1,
            'garantias' => ['recibo_sueldo', 'garante'],
        ];
    }
}
