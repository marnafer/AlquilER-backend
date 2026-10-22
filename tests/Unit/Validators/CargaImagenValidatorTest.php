<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\CargaImagenValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CargaImagenValidatorTest extends TestCase
{
    private CargaImagenValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CargaImagenValidator();
    }

    public function test_validate_sin_imagen_falla(): void
    {
        $this->assertSame(
            'Debe enviar una imagen',
            $this->validator->validate([])
        );
    }

    #[DataProvider('archivosSinNombreTemporalValido')]
    public function test_validate_sin_nombre_temporal_valido_falla(
        array $file
    ): void {
        $this->assertSame(
            'Debe enviar una imagen',
            $this->validator->validate($file)
        );
    }

    public static function archivosSinNombreTemporalValido(): array
    {
        return [
            'sin_tmp_name' => [
                ['error' => UPLOAD_ERR_OK, 'size' => 100],
            ],
            'tmp_name_no_string' => [
                ['tmp_name' => 123, 'error' => UPLOAD_ERR_OK, 'size' => 100],
            ],
            'tmp_name_vacio' => [
                ['tmp_name' => '', 'error' => UPLOAD_ERR_OK, 'size' => 100],
            ],
        ];
    }

    public function test_validate_archivo_que_no_proviene_de_subida_http_falla(): void
    {
        $this->assertSame(
            'Debe enviar una imagen',
            $this->validator->validate([
                'tmp_name' => __FILE__,
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ])
        );
    }

    public function test_validate_sin_archivo_seleccionado_falla(): void
    {
        $this->assertSame(
            'No se pudo cargar la imagen',
            $this->validator->validate([
                'error' => UPLOAD_ERR_NO_FILE,
            ])
        );
    }

    #[DataProvider('erroresDeSubida')]
    public function test_validate_con_error_de_subida_falla(
        int $error
    ): void {
        $this->assertSame(
            'No se pudo cargar la imagen',
            $this->validator->validate([
                'tmp_name' => '/tmp/archivo_prueba',
                'error' => $error,
                'size' => 1024,
            ])
        );
    }

    public static function erroresDeSubida(): array
    {
        return [
            'supera limite PHP' => [UPLOAD_ERR_INI_SIZE],
            'supera limite formulario' => [UPLOAD_ERR_FORM_SIZE],
            'carga parcial' => [UPLOAD_ERR_PARTIAL],
            'no se pudo guardar temporal' => [UPLOAD_ERR_NO_TMP_DIR],
            'fallo de escritura' => [UPLOAD_ERR_CANT_WRITE],
            'extension detuvo subida' => [UPLOAD_ERR_EXTENSION],
        ];
    }
}
