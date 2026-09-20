<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GestorArchivosLocales;
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
}