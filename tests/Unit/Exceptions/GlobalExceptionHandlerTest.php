<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\GlobalExceptionHandler;
use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ExcepcionControladaConCodigoRaro extends BadRequestException
{
    public function __construct()
    {
        \RuntimeException::__construct('mensaje raro', 200);
    }
}

final class GlobalExceptionHandlerTest extends TestCase
{
    public static function proveedorExcepciones(): array
    {
        return [
            'BadRequest' => [BadRequestException::class, 400, 'Solicitud inválida'],
            'Unauthorized' => [UnauthorizedException::class, 401, 'No autorizado'],
            'Forbidden' => [ForbiddenException::class, 403, 'Acceso denegado'],
            'NotFound' => [NotFoundException::class, 404, 'Recurso no encontrado'],
            'MethodNotAllowed' => [MethodNotAllowedException::class, 405, 'Método no permitido'],
            'Conflict' => [ConflictException::class, 409, 'Conflicto de datos'],
        ];
    }

    #[DataProvider('proveedorExcepciones')]
    public function test_constructor_define_codigo_y_mensaje_por_defecto(
        string $clase,
        int $codigo,
        string $mensaje
    ): void {
        $excepcion = new $clase();

        $this->assertSame($codigo, $excepcion->getCode());
        $this->assertSame($mensaje, $excepcion->getMessage());
    }

    public function test_constructor_acepta_mensaje_personalizado_y_previous(): void
    {
        $anterior = new \RuntimeException('causa original');

        $excepcion = new NotFoundException('mensaje custom', $anterior);

        $this->assertSame('mensaje custom', $excepcion->getMessage());
        $this->assertSame(404, $excepcion->getCode());
        $this->assertSame($anterior, $excepcion->getPrevious());
    }

    public function test_validation_exception_normaliza_errores_y_fija_422(): void
    {
        $excepcion = new ValidationException([
            'email' => 'requerido',
            'nombre' => ['muy corto', 'invalido'],
        ]);

        $this->assertSame(422, $excepcion->getCode());
        $this->assertSame('Error de validación', $excepcion->getMessage());
        $this->assertSame([
            'email' => ['requerido'],
            'nombre' => ['muy corto', 'invalido'],
        ], $excepcion->errors());
    }

    public function test_validation_exception_acepta_mensaje_personalizado(): void
    {
        $excepcion = new ValidationException(['campo' => 'error'], 'Datos inválidos');

        $this->assertSame('Datos inválidos', $excepcion->getMessage());
        $this->assertSame(['campo' => ['error']], $excepcion->errors());
    }

    #[DataProvider('proveedorExcepciones')]
    public function test_maneja_excepcion_controlada_con_su_codigo(
        string $clase,
        int $codigo
    ): void {
        $response = GlobalExceptionHandler::handle(new $clase('mensaje controlado'));

        $this->assertSame($codigo, $response->status());
        $this->assertFalse($response->payload()['success']);
        $this->assertSame('mensaje controlado', $response->payload()['error']);
        $this->assertArrayNotHasKey('validation_errors', $response->payload());
    }

    public function test_maneja_validation_exception_incluyendo_los_errores(): void
    {
        $response = GlobalExceptionHandler::handle(
            new ValidationException(['email' => 'requerido'])
        );

        $this->assertSame(422, $response->status());
        $this->assertFalse($response->payload()['success']);
        $this->assertSame('Error de validación', $response->payload()['error']);
        $this->assertSame(
            ['email' => ['requerido']],
            $response->payload()['validation_errors']
        );
    }

    public function test_maneja_excepcion_no_controlada_con_500_generico(): void
    {
        $ruta = $this->rutaLogTempora();
        $original = is_file($ruta) ? (string) file_get_contents($ruta) : null;

        $errorLogOriginal = ini_get('error_log');
        $errorLogTemporal = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'error_log_test_'
            . bin2hex(random_bytes(4))
            . '.log';
        ini_set('error_log', $errorLogTemporal);

        try {
            $response = GlobalExceptionHandler::handle(
                new \RuntimeException('boom SQLSTATE 42S02 tabla usuarios')
            );

            $this->assertSame(500, $response->status());
            $this->assertFalse($response->payload()['success']);
            $this->assertSame(
                'Error interno del servidor',
                $response->payload()['error']
            );

            $this->assertFileExists($ruta);
            $contenido = (string) file_get_contents($ruta);
            $this->assertStringContainsString('RuntimeException', $contenido);
            $this->assertStringContainsString('boom SQLSTATE 42S02 tabla usuarios', $contenido);
            $this->assertStringContainsString('origen:', $contenido);
        } finally {
            $this->restaurarLog($ruta, $original);

            if ($errorLogOriginal !== false) {
                ini_set('error_log', (string) $errorLogOriginal);
            } else {
                ini_set('error_log', '');
            }

            if (is_file($errorLogTemporal)) {
                @unlink($errorLogTemporal);
            }
        }
    }

    public function test_maneja_excepcion_controlada_no_registra_en_el_log(): void
    {
        $ruta = $this->rutaLogTempora();
        $antes = is_file($ruta) ? (string) file_get_contents($ruta) : null;

        GlobalExceptionHandler::handle(new NotFoundException());

        $despues = is_file($ruta) ? (string) file_get_contents($ruta) : null;

        $this->assertSame($antes, $despues);
    }

    public function test_maneja_excepcion_controlada_con_codigo_fuera_de_rango_como_500(): void
    {
        $response = GlobalExceptionHandler::handle(new ExcepcionControladaConCodigoRaro());

        $this->assertSame(500, $response->status());
        $this->assertFalse($response->payload()['success']);
        $this->assertSame('mensaje raro', $response->payload()['error']);
    }

    private function rutaLogTempora(): string
    {
        return dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'errores.log';
    }

    private function restaurarLog(string $ruta, ?string $original): void
    {
        if ($original !== null) {
            file_put_contents($ruta, $original);
        } elseif (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
