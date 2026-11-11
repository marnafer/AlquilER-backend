<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\CargaImagenValidator;
use PHPUnit\Framework\TestCase;

final class CargaImagenValidatorCoverageTest extends TestCase
{
    private function validator(): CargaImagenValidator
    {
        return new class extends CargaImagenValidator {
            protected function isUploadedFile(string $path): bool
            {
                return true;
            }
        };
    }

    public function test_size_invalido(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, 'x');

        $this->assertSame(
            'No se pudo determinar el tamaño de la imagen',
            $this->validator()->validate([
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
            ])
        );

        @unlink($path);
    }

    public function test_size_demasiado_grande(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, 'x');

        $this->assertSame(
            'La imagen supera 15 MB',
            $this->validator()->validate([
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => 16 * 1024 * 1024,
            ])
        );

        @unlink($path);
    }

    public function test_formato_no_permitido(): void
    {
        if (!function_exists('imagegif')) {
            $this->markTestSkipped('GD no soporta GIF');
        }

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'img.gif';
        $imagen = imagecreatetruecolor(2, 2);
        imagegif($imagen, $path);
        imagedestroy($imagen);

        $this->assertSame(
            'Formato no permitido. Solo se permiten imágenes JPEG y PNG',
            $this->validator()->validate([
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => 100,
            ])
        );

        @unlink($path);
    }

    public function test_dimensiones_invalidas(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'falso.jpg';
        file_put_contents($path, "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100));

        $this->assertSame(
            'El archivo no contiene una imagen válida',
            $this->validator()->validate([
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => 100,
            ])
        );

        @unlink($path);
    }

    public function test_imagen_valida_devuelve_null(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'valida.jpg';
        $imagen = imagecreatetruecolor(2, 2);
        imagejpeg($imagen, $path, 90);
        imagedestroy($imagen);

        $this->assertNull(
            $this->validator()->validate([
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => 100,
            ])
        );

        @unlink($path);
    }

    public function test_dimensiones_demasiado_grandes(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grande.jpg';
        $imagen = imagecreatetruecolor(12001, 1);
        imagejpeg($imagen, $path, 90);
        imagedestroy($imagen);

        $this->assertSame(
            'Las dimensiones de la imagen son demasiado grandes',
            $this->validator()->validate([
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => 100,
            ])
        );

        @unlink($path);
    }
}