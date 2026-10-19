<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Repositories\GestorArchivosLocales;
use App\Exceptions\BadRequestException;

class GestorArchivosLocalesTest extends TestCase
{
    private string $tempDir;
    private GestorArchivosLocales $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = new GestorArchivosLocales();
        $this->tempDir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_it_throws_exception_when_directory_creation_fails(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'No se pudo crear el directorio de imágenes'
        );

        $invalidPath = tempnam(sys_get_temp_dir(), 'test_file');

        try {
            $file = [
                'name' => 'imagen.jpg',
                'tmp_name' => 'dummy_tmp',
            ];

            $this->gestor->upload($file, $invalidPath);
        } finally {
            if (file_exists($invalidPath)) {
                unlink($invalidPath);
            }
        }
    }

    public function test_it_throws_exception_when_file_format_is_invalid(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Formato de imagen no permitido');

        $file = [
            'name' => 'archivo.txt',
            'tmp_name' => __FILE__,
        ];

        $this->gestor->upload($file, $this->tempDir);
    }

    public function test_it_detects_a_real_image_but_fails_to_move_it(): void
    {
        $tmp = sys_get_temp_dir() . '/real_image_' . uniqid() . '.png';

        file_put_contents(
            $tmp,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQAY3Y2wAAAAAElFTkSuQmCC'
            )
        );

        set_error_handler(static fn (): bool => true);

        try {
            $this->gestor->upload(
                ['name' => 'imagen.png', 'tmp_name' => $tmp],
                $this->tempDir
            );
            $this->fail('Se esperaba BadRequestException');
        } catch (BadRequestException $e) {
            $this->assertSame('Error al guardar la imagen', $e->getMessage());
        } finally {
            restore_error_handler();

            if (file_exists($tmp)) {
                unlink($tmp);
            }
        }
    }

    public function test_delete_removes_an_existing_file(): void
    {
        $publicDir = dirname(__DIR__, 3) . '/public';
        $archivo = $publicDir . '/__gestor_delete_test.txt';

        file_put_contents($archivo, 'contenido');

        $this->assertFileExists($archivo);

        try {
            $this->gestor->delete('/__gestor_delete_test.txt');

            $this->assertFileDoesNotExist($archivo);
        } finally {
            if (file_exists($archivo)) {
                unlink($archivo);
            }
        }
    }

    public function test_delete_of_a_missing_file_returns_silently(): void
    {
        $this->gestor->delete('/__no_existe_' . uniqid() . '.txt');

        $this->assertTrue(true);
    }
}