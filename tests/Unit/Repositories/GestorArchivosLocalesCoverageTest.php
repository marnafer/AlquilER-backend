<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\BadRequestException;
use App\Repositories\GestorArchivosLocales;
use PHPUnit\Framework\TestCase;

final class GestorArchivosLocalesCoverageTest extends TestCase
{
    private string $directory;
    private string $temporal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'alquiler-gestor-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);

        $imagen = imagecreatetruecolor(2, 2);
        $this->temporal = $this->directory . DIRECTORY_SEPARATOR . 'temp.jpg';
        imagejpeg($imagen, $this->temporal, 90);
        imagedestroy($imagen);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->directory) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            @unlink($this->directory . DIRECTORY_SEPARATOR . $file);
        }
        @rmdir($this->directory);
        parent::tearDown();
    }

    public function test_upload_falla_si_no_se_puede_escribir_en_directorio(): void
    {
        $gestor = new class extends GestorArchivosLocales {
            protected function isWritable(string $path): bool
            {
                return false;
            }
        };

        $this->expectException(BadRequestException::class);
        $gestor->upload($this->temporal, $this->directory);
    }

    public function test_upload_falla_si_no_se_puede_copiar(): void
    {
        $gestor = new class extends GestorArchivosLocales {
            protected function copyFile(string $source, string $destination): bool
            {
                return false;
            }
        };

        $this->expectException(BadRequestException::class);
        $gestor->upload($this->temporal, $this->directory);
    }

    public function test_upload_falla_si_no_se_puede_verificar(): void
    {
        $gestor = new class extends GestorArchivosLocales {
            protected function fileSize(string $path): int|false
            {
                return 0;
            }
        };

        $this->expectException(BadRequestException::class);
        $gestor->upload($this->temporal, $this->directory);
    }

    public function test_delete_falla_si_no_se_puede_eliminar(): void
    {
        $ruta = '/coverage-delete.jpg';
        $fisica = dirname(__DIR__, 3) . '/public' . $ruta;
        file_put_contents($fisica, 'x');

        $gestor = new class extends GestorArchivosLocales {
            protected function deleteFile(string $path): bool
            {
                return false;
            }
        };

        try {
            $this->expectException(BadRequestException::class);
            $gestor->delete($ruta);
        } finally {
            @unlink($fisica);
        }
    }
}