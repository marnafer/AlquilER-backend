<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\OperacionArchivosException;
use App\Repositories\PropiedadImagenRepositoryInterface;
use App\Services\LimpiadorArchivosLocales;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class LimpiadorArchivosLocalesTest extends TestCase
{
    /** @var PropiedadImagenRepositoryInterface&MockObject */
    private PropiedadImagenRepositoryInterface $repository;

    private LimpiadorArchivosLocales $limpiador;

    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );

        $this->limpiador = new LimpiadorArchivosLocales(
            $this->repository
        );

        $this->directorio = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-limpiador-'
            . bin2hex(random_bytes(6));

        mkdir($this->directorio, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->eliminarDirectorio($this->directorio);

        parent::tearDown();
    }

    public function test_detecta_archivos_huerfanos_sin_eliminarlos_en_modo_simulacion(): void
    {
        $this->crearArchivo('registrada.jpg');
        $this->crearArchivoAntiguo('huerfana.jpg');

        $this->configurarRutas([
            '/uploads/propiedades/registrada.jpg',
        ]);

        $resultado = $this->limpiador->limpiar(
            $this->directorio
        );

        $this->assertSame(2, $resultado['archivos_analizados']);
        $this->assertSame(1, $resultado['archivos_referenciados']);
        $this->assertSame(1, $resultado['archivos_huerfanos']);
        $this->assertSame(0, $resultado['archivos_eliminados']);
        $this->assertSame(
            0,
            $resultado['archivos_referenciados_inexistentes']
        );

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'huerfana.jpg'
        );
    }

    public function test_elimina_archivos_huerfanos_en_modo_ejecucion(): void
    {
        $this->crearArchivo('registrada.jpg');
        $this->crearArchivoAntiguo('huerfana.jpg');

        $this->configurarRutas([
            '/uploads/propiedades/registrada.jpg',
        ]);

        $resultado = $this->limpiador->limpiar(
            $this->directorio,
            true
        );

        $this->assertSame(2, $resultado['archivos_analizados']);
        $this->assertSame(1, $resultado['archivos_referenciados']);
        $this->assertSame(1, $resultado['archivos_huerfanos']);
        $this->assertSame(1, $resultado['archivos_eliminados']);

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'registrada.jpg'
        );

        $this->assertFileDoesNotExist(
            $this->directorio . DIRECTORY_SEPARATOR . 'huerfana.jpg'
        );
    }

    public function test_no_elimina_archivos_recientes_aunque_esten_huerfanos(): void
    {
        $this->crearArchivo('reciente.jpg');

        $this->configurarRutas([]);

        $resultado = $this->limpiador->limpiar(
            $this->directorio,
            true
        );

        $this->assertSame(1, $resultado['archivos_analizados']);
        $this->assertSame(0, $resultado['archivos_huerfanos']);
        $this->assertSame(0, $resultado['archivos_eliminados']);

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'reciente.jpg'
        );
    }

    public function test_detecta_referencia_de_bd_cuyo_archivo_no_existe(): void
    {
        $this->crearArchivo('existente.jpg');

        $this->configurarRutas([
            '/uploads/propiedades/existente.jpg',
            '/uploads/propiedades/inexistente.jpg',
        ]);

        $resultado = $this->limpiador->limpiar(
            $this->directorio
        );

        $this->assertSame(1, $resultado['archivos_analizados']);
        $this->assertSame(2, $resultado['archivos_referenciados']);
        $this->assertSame(0, $resultado['archivos_huerfanos']);
        $this->assertSame(
            1,
            $resultado['archivos_referenciados_inexistentes']
        );

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'existente.jpg'
        );
    }

    public function test_ignora_archivos_que_no_son_imagenes_permitidas(): void
    {
        $this->crearArchivo('documento.txt');
        $this->crearArchivo('imagen.gif');
        $this->crearArchivo('imagen.webp');
        $this->crearArchivoAntiguo('imagen.jpg');

        $this->configurarRutas([]);

        $resultado = $this->limpiador->limpiar(
            $this->directorio
        );

        $this->assertSame(1, $resultado['archivos_analizados']);
        $this->assertSame(1, $resultado['archivos_huerfanos']);

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'documento.txt'
        );

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'imagen.gif'
        );

        $this->assertFileExists(
            $this->directorio . DIRECTORY_SEPARATOR . 'imagen.webp'
        );
    }

    public function test_acepta_rutas_con_barras_invertidas(): void
    {
        $this->crearArchivo('imagen.jpg');

        $this->configurarRutas([
            '\\uploads\\propiedades\\imagen.jpg',
        ]);

        $resultado = $this->limpiador->limpiar(
            $this->directorio
        );

        $this->assertSame(1, $resultado['archivos_analizados']);
        $this->assertSame(1, $resultado['archivos_referenciados']);
        $this->assertSame(0, $resultado['archivos_huerfanos']);
        $this->assertSame(
            0,
            $resultado['archivos_referenciados_inexistentes']
        );
    }

    public function test_lanza_excepcion_si_el_directorio_no_existe(): void
    {
        $directorioInexistente = $this->directorio . '-inexistente';

        $this->expectException(
            OperacionArchivosException::class
        );

        $this->expectExceptionMessage(
            'El directorio de imágenes no existe'
        );

        $this->limpiador->limpiar(
            $directorioInexistente
        );
    }

    private function configurarRutas(array $rutas): void
    {
        $this->repository
            ->expects($this->once())
            ->method('recorrerRutas')
            ->willReturnCallback(
                function (callable $callback) use ($rutas): void {
                    foreach ($rutas as $ruta) {
                        $callback($ruta);
                    }
                }
            );
    }

    private function crearArchivo(string $nombre): void
    {
        file_put_contents(
            $this->directorio
            . DIRECTORY_SEPARATOR
            . $nombre,
            'contenido de prueba'
        );
    }

    private function crearArchivoAntiguo(string $nombre): void
    {
        $ruta = $this->directorio
            . DIRECTORY_SEPARATOR
            . $nombre;

        $this->crearArchivo($nombre);

        touch(
            $ruta,
            time() - 86400 - 60
        );
    }

    private function eliminarDirectorio(string $directorio): void
    {
        if (!is_dir($directorio)) {
            return;
        }

        $archivos = scandir($directorio);

        if ($archivos === false) {
            return;
        }

        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            $ruta = $directorio
                . DIRECTORY_SEPARATOR
                . $archivo;

            if (is_file($ruta)) {
                unlink($ruta);
            }
        }

        rmdir($directorio);
    }
}