<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\BadRequestException;
use App\Repositories\ProcesadorImagenes;
use PHPUnit\Framework\TestCase;

final class ProcesadorImagenesCoverageTest extends TestCase
{
    public function test_rechaza_imagen_con_dimension_demasiado_grande(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grande.jpg';
        $imagen = imagecreatetruecolor(12001, 1);
        imagejpeg($imagen, $path, 90);
        imagedestroy($imagen);

        $procesador = new ProcesadorImagenes();

        $this->expectException(BadRequestException::class);
        try {
            $procesador->procesar(['tmp_name' => $path]);
        } finally {
            @unlink($path);
        }
    }

    public function test_rechaza_formato_no_permitido(): void
    {
        if (!function_exists('imagegif')) {
            $this->markTestSkipped('GD no soporta GIF');
        }

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imagen.gif';
        $imagen = imagecreatetruecolor(2, 2);
        imagegif($imagen, $path);
        imagedestroy($imagen);

        $procesador = new ProcesadorImagenes();

        $this->expectException(BadRequestException::class);
        try {
            $procesador->procesar(['tmp_name' => $path]);
        } finally {
            @unlink($path);
        }
    }
}