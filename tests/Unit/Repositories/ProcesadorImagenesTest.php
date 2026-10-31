<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\BadRequestException;
use App\Repositories\ProcesadorImagenes;
use PHPUnit\Framework\TestCase;

final class ProcesadorImagenesTest extends TestCase
{
    private ProcesadorImagenes $procesador;

    /** @var list<string> */
    private array $archivosTemporales = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (
            !function_exists('imagecreatetruecolor')
            || !function_exists('imagejpeg')
            || !function_exists('imagepng')
            || !function_exists('imagecopyresampled')
        ) {
            self::markTestSkipped(
                'Se necesita la extensión GD para ejecutar estas pruebas.'
            );
        }

        $this->procesador = new ProcesadorImagenes();
    }

    protected function tearDown(): void
    {
        foreach ($this->archivosTemporales as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }

        parent::tearDown();
    }

    public function test_procesa_jpeg_pequeno_sin_ampliarlo(): void
    {
        $origen = $this->crearImagen('jpeg', 640, 480);

        $salida = $this->procesador->procesar([
            'tmp_name' => $origen,
        ]);

        $this->registrarTemporal($salida);

        $informacion = getimagesize($salida);

        self::assertNotFalse($informacion);
        self::assertSame(640, $informacion[0]);
        self::assertSame(480, $informacion[1]);
        self::assertSame('image/jpeg', $informacion['mime']);
        self::assertGreaterThan(0, filesize($salida));
    }

    public function test_redimensiona_imagen_grande_conservando_proporcion(): void
    {
        $origen = $this->crearImagen('jpeg', 2400, 1200);

        $salida = $this->procesador->procesar([
            'tmp_name' => $origen,
        ]);

        $this->registrarTemporal($salida);

        $informacion = getimagesize($salida);

        self::assertNotFalse($informacion);
        self::assertSame(1920, $informacion[0]);
        self::assertSame(960, $informacion[1]);
        self::assertSame('image/jpeg', $informacion['mime']);
    }

    public function test_convierte_png_a_jpeg(): void
    {
        $origen = $this->crearImagen('png', 300, 200);

        $salida = $this->procesador->procesar([
            'tmp_name' => $origen,
        ]);

        $this->registrarTemporal($salida);

        $informacion = getimagesize($salida);

        self::assertNotFalse($informacion);
        self::assertSame(300, $informacion[0]);
        self::assertSame(200, $informacion[1]);
        self::assertSame('image/jpeg', $informacion['mime']);
    }

    public function test_rechaza_un_archivo_que_no_es_una_imagen(): void
    {
        $origen = tempnam(sys_get_temp_dir(), 'alquiler_test_');

        self::assertNotFalse($origen);

        $this->registrarTemporal($origen);

        file_put_contents(
            $origen,
            'Esto no es una imagen válida.'
        );

        try {
            $this->procesador->procesar([
                'tmp_name' => $origen,
            ]);

            self::fail(
                'Se esperaba BadRequestException.'
            );
        } catch (BadRequestException $e) {
            self::assertSame(
                'El archivo no contiene una imagen válida',
                $e->getMessage()
            );
        }
    }

    public function test_rechaza_una_ruta_inexistente(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'No se puede leer la imagen recibida'
        );

        $this->procesador->procesar([
            'tmp_name' => sys_get_temp_dir()
                . '/archivo_inexistente_' . bin2hex(random_bytes(8)),
        ]);
    }

    public function test_rechaza_un_formato_no_permitido(): void
    {
        // GIF válido de 1 x 1 píxel.
        $contenido = base64_decode(
            'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
            true
        );

        self::assertNotFalse($contenido);

        $origen = tempnam(sys_get_temp_dir(), 'alquiler_test_');

        self::assertNotFalse($origen);

        $this->registrarTemporal($origen);
        file_put_contents($origen, $contenido);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'Formato no permitido. Solo se permiten imágenes JPEG y PNG'
        );

        $this->procesador->procesar([
            'tmp_name' => $origen,
        ]);
    }

    public function test_rechaza_un_array_sin_ruta_temporal(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'No se puede leer la imagen recibida'
        );

        $this->procesador->procesar([]);
    }

    private function crearImagen(
        string $formato,
        int $ancho,
        int $alto
    ): string {
        $imagen = imagecreatetruecolor($ancho, $alto);

        self::assertNotFalse($imagen);

        $fondo = imagecolorallocate($imagen, 80, 140, 200);
        imagefill($imagen, 0, 0, $fondo);

        $ruta = tempnam(sys_get_temp_dir(), 'alquiler_origen_');

        self::assertNotFalse($ruta);

        $this->registrarTemporal($ruta);

        $guardada = match ($formato) {
            'jpeg' => imagejpeg($imagen, $ruta, 90),
            'png' => imagepng($imagen, $ruta),
            default => false,
        };

        imagedestroy($imagen);

        self::assertTrue(
            $guardada,
            'No se pudo generar la imagen temporal de prueba.'
        );

        return $ruta;
    }

    private function registrarTemporal(string $ruta): void
    {
        $this->archivosTemporales[] = $ruta;
    }
}
