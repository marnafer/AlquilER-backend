<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\BadRequestException;
use App\Repositories\GestorArchivosLocales;
use GdImage;
use Tests\TestCase;

final class GestorArchivosLocalesTest extends TestCase
{
    private string $tempDir;
    private GestorArchivosLocales $gestor;
    private array $archivosTemporales = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = new GestorArchivosLocales();
        $this->tempDir = sys_get_temp_dir()
            . '/test_uploads_'
            . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') ?: [] as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }

        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }

        foreach ($this->archivosTemporales as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }

        parent::tearDown();
    }

    public function test_it_throws_exception_when_directory_creation_fails(): void
    {
        $imagen = $this->crearJpegTemporal();

        // tempnam crea un archivo, no un directorio.
        $rutaArchivo = tempnam(sys_get_temp_dir(), 'test_file_');

        $this->assertNotFalse($rutaArchivo);

        try {
            try {
                $this->gestor->upload($imagen, $rutaArchivo);

                $this->fail('Se esperaba BadRequestException');
            } catch (BadRequestException $e) {
                $this->assertSame(
                    'No se pudo crear el directorio de imágenes',
                    $e->getMessage()
                );
            }
        } finally {
            if (is_file($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }
    }

    public function test_it_throws_exception_when_file_format_is_invalid(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'El archivo procesado no es un JPEG válido'
        );

        $this->gestor->upload(__FILE__, $this->tempDir);
    }

    public function test_it_throws_exception_when_temporary_file_does_not_exist(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'No se puede leer la imagen procesada'
        );

        $this->gestor->upload(
            sys_get_temp_dir() . '/no_existe_' . bin2hex(random_bytes(8)),
            $this->tempDir
        );
    }

    public function test_it_saves_a_processed_jpeg_with_a_generated_name(): void
    {
        $imagen = $this->crearJpegTemporal();

        $nombreArchivo = $this->gestor->upload(
            $imagen,
            $this->tempDir
        );

        $destino = $this->tempDir . '/' . $nombreArchivo;

        $this->assertMatchesRegularExpression(
            '/\A[a-f0-9]{32}\.jpg\z/',
            $nombreArchivo
        );

        $this->assertFileExists($destino);
        $this->assertGreaterThan(0, filesize($destino));

        $informacion = getimagesize($destino);

        $this->assertNotFalse($informacion);
        $this->assertSame('image/jpeg', $informacion['mime']);
    }

    public function test_delete_removes_an_existing_file(): void
    {
        $publicDir = dirname(__DIR__, 3) . '/public';
        $archivo = $publicDir . '/__gestor_delete_test_' 
            . bin2hex(random_bytes(8)) . '.txt';

        file_put_contents($archivo, 'contenido');

        $this->assertFileExists($archivo);

        try {
            $rutaRelativa = '/' . basename($archivo);

            $this->gestor->delete($rutaRelativa);

            $this->assertFileDoesNotExist($archivo);
        } finally {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
    }

    public function test_delete_of_a_missing_file_returns_silently(): void
    {
        $this->gestor->delete(
            '/__no_existe_' . bin2hex(random_bytes(8)) . '.jpg'
        );

        $this->assertTrue(true);
    }

    private function crearJpegTemporal(): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped(
                'La extensión GD es necesaria para generar el JPEG de prueba.'
            );
        }

        $imagen = imagecreatetruecolor(2, 2);

        $this->assertInstanceOf(GdImage::class, $imagen);

        $ruta = tempnam(sys_get_temp_dir(), 'test_jpeg_');

        $this->assertNotFalse($ruta);

        $this->archivosTemporales[] = $ruta;

        try {
            if (!imagejpeg($imagen, $ruta, 80)) {
                $this->fail('No se pudo generar el JPEG de prueba.');
            }
        } finally {
            imagedestroy($imagen);
        }

        return $ruta;
    }

    public function test_upload_preserves_processed_jpeg_dimensions(): void
    {
        $imagen = $this->crearJpegTemporal();

        $nombreArchivo = $this->gestor->upload(
            $imagen,
            $this->tempDir
        );

        $destino = $this->tempDir . '/' . $nombreArchivo;

        $informacion = getimagesize($destino);

        $this->assertNotFalse($informacion);
        $this->assertSame(2, $informacion[0]);
        $this->assertSame(2, $informacion[1]);
        $this->assertSame('image/jpeg', $informacion['mime']);
    }

    public function test_upload_preserves_the_temporary_source_file(): void
    {
        $imagen = $this->crearJpegTemporal();

        $contenidoOriginal = file_get_contents($imagen);

        $nombreArchivo = $this->gestor->upload(
            $imagen,
            $this->tempDir
        );

        $destino = $this->tempDir . '/' . $nombreArchivo;

        $this->assertFileExists($imagen);
        $this->assertSame(
            $contenidoOriginal,
            file_get_contents($imagen)
        );
        $this->assertFileExists($destino);
    }
}
