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

    /** @test */
    public function test_it_throws_exception_when_directory_creation_fails()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('No se pudo crear el directorio de imágenes');

        $invalidPath = tempnam(sys_get_temp_dir(), 'test_file');

        try {
            $file = [
                'name' => 'imagen.jpg',
                'tmp_name' => 'dummy_tmp'
            ];
            
            // Forzar fallo de mkdir pasando un archivo existente como si fuera un directorio
            $this->gestor->upload($file, $invalidPath);
        } finally {
            if (file_exists($invalidPath)) {
                unlink($invalidPath);
            }
        }
    }

    /** @test */
    public function test_it_throws_exception_when_move_uploaded_file_fails()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error al guardar la imagen');

        $file = [
            'name' => 'avatar.png',
            'tmp_name' => 'ruta_temporal_falsa_que_no_existe'
        ];

        $this->gestor->upload($file, $this->tempDir);
    }
}