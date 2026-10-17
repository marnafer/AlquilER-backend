<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\CargaImagenValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Validators\CargaImagenValidatorDobles;

final class CargaImagenValidatorTest extends TestCase
{
    private CargaImagenValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/CargaImagenValidatorDobles.php';

        CargaImagenValidatorDobles::reiniciar();

        // Por defecto simulamos un archivo efectivamente subido para poder
        // ejercitar las validaciones posteriores al guard de subida.
        CargaImagenValidatorDobles::$forzarArchivoSubido = true;

        $this->validator = new CargaImagenValidator();
    }

    protected function tearDown(): void
    {
        CargaImagenValidatorDobles::reiniciar();

        parent::tearDown();
    }

    #[DataProvider('archivosSinImagenSubida')]
    public function test_validate_sin_imagen_subida_falla(
        array $file
    ): void {
        $this->assertSame(
            'Debe enviar una imagen',
            $this->validator->validate($file)
        );
    }

    public static function archivosSinImagenSubida(): array
    {
        return [
            'arreglo_vacio' => [[]],
            'sin_tmp_name' => [
                ['error' => UPLOAD_ERR_OK, 'size' => 100],
            ],
            'tmp_name_no_string' => [
                ['tmp_name' => 123, 'error' => UPLOAD_ERR_OK, 'size' => 100],
            ],
        ];
    }

    public function test_validate_archivo_no_proveniente_de_subida_falla(): void
    {
        // Sin forzar la subida, is_uploaded_file() real devuelve false.
        CargaImagenValidatorDobles::$forzarArchivoSubido = false;

        $this->assertSame(
            'Debe enviar una imagen',
            $this->validator->validate([
                'tmp_name' => __FILE__,
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ])
        );
    }

    #[DataProvider('archivosConErrorDeSubida')]
    public function test_validate_con_error_de_subida_falla(
        array $file
    ): void {
        $this->assertSame(
            'No se pudo cargar la imagen',
            $this->validator->validate($file)
        );
    }

    public static function archivosConErrorDeSubida(): array
    {
        return [
            'sin_key_error' => [
                ['tmp_name' => 'C:\\xampp\\tmp\\php123.tmp', 'size' => 1024],
            ],
            'error_de_tamano' => [
                [
                    'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                    'error' => UPLOAD_ERR_INI_SIZE,
                    'size' => 1024,
                ],
            ],
            'error_como_cadena_cero' => [
                [
                    'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                    'error' => '0',
                    'size' => 1024,
                ],
            ],
        ];
    }

    #[DataProvider('archivosConTamanoIndeterminado')]
    public function test_validate_sin_tamano_determinable_falla(
        array $file
    ): void {
        $this->assertSame(
            'No se pudo determinar el tamaño de la imagen',
            $this->validator->validate($file)
        );
    }

    public static function archivosConTamanoIndeterminado(): array
    {
        return [
            'sin_key_size' => [
                ['tmp_name' => 'C:\\xampp\\tmp\\php123.tmp', 'error' => UPLOAD_ERR_OK],
            ],
            'size_no_numerico' => [
                [
                    'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 'no-numerico',
                ],
            ],
        ];
    }

    public function test_validate_imagen_mayor_a_5mb_falla(): void
    {
        $this->assertSame(
            'La imagen supera 5MB',
            $this->validator->validate([
                'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => (5 * 1024 * 1024) + 1,
            ])
        );
    }

    public function test_validate_imagen_de_5mb_exactos_es_valida(): void
    {
        CargaImagenValidatorDobles::$mimeForzado = 'image/png';

        $this->assertNull(
            $this->validator->validate([
                'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 5 * 1024 * 1024,
            ])
        );
    }

    public function test_validate_falla_si_no_se_puede_abrir_finfo(): void
    {
        CargaImagenValidatorDobles::$fallaAperturaDeFinfo = true;

        $this->assertSame(
            'No se pudo determinar el formato de la imagen',
            $this->validator->validate([
                'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ])
        );
    }

    public function test_validate_falla_si_no_se_puede_leer_el_mime(): void
    {
        CargaImagenValidatorDobles::$fallaLecturaDeFinfo = true;

        $this->assertSame(
            'No se pudo determinar el formato de la imagen',
            $this->validator->validate([
                'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ])
        );
    }

    #[DataProvider('mimesNoPermitidos')]
    public function test_validate_formato_no_permitido_falla(
        string $mime
    ): void {
        CargaImagenValidatorDobles::$mimeForzado = $mime;

        $this->assertSame(
            'Formato no permitido. Solo se permiten imágenes JPEG y PNG',
            $this->validator->validate([
                'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ])
        );
    }

    public static function mimesNoPermitidos(): array
    {
        return [
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'texto' => ['text/plain'],
        ];
    }

    #[DataProvider('mimesPermitidos')]
    public function test_validate_formato_permitido_devuelve_null(
        string $mime
    ): void {
        CargaImagenValidatorDobles::$mimeForzado = $mime;

        $this->assertNull(
            $this->validator->validate([
                'tmp_name' => 'C:\\xampp\\tmp\\php123.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 4096,
            ])
        );
    }

    public static function mimesPermitidos(): array
    {
        return [
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
        ];
    }
}
