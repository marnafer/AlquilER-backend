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
use App\Helpers\ErrorLog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
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
    /**
     * Ejecuta el handler capturando la salida y el código HTTP.
     *
     * @return array{0:int, 1:string, 2:array}
     */
    private function manejar(Throwable $excepcion): array
    {
        ob_start();

        try {
            GlobalExceptionHandler::handle($excepcion);
        } catch (\Error $error) {
            if ($error->getMessage() !== '__RESPONSE_SENT__') {
                ob_end_clean();
                throw $error;
            }
        }

        $cuerpo = (string) ob_get_clean();
        $status = (int) http_response_code();
        $json = json_decode($cuerpo, true);

        return [$status, $cuerpo, is_array($json) ? $json : []];
    }

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
        [$status, $cuerpo, $json] = $this->manejar(new $clase('mensaje controlado'));

        $this->assertSame($codigo, $status);
        $this->assertStringContainsString('"success": false', $cuerpo);
        $this->assertFalse($json['success']);
        $this->assertSame('mensaje controlado', $json['error']);
        $this->assertArrayNotHasKey('validation_errors', $json);
    }

    public function test_maneja_validation_exception_incluyendo_los_errores(): void
    {
        $excepcion = new ValidationException(['email' => 'requerido']);

        [$status, $cuerpo, $json] = $this->manejar($excepcion);

        $this->assertSame(422, $status);
        $this->assertFalse($json['success']);
        $this->assertSame('Error de validación', $json['error']);
        $this->assertSame(['email' => ['requerido']], $json['validation_errors']);
    }

    public function test_maneja_excepcion_no_controlada_con_500_generico(): void
    {
        $ruta = ErrorLog::ruta();
        $existia = is_file($ruta);
        $original = $existia ? (string) file_get_contents($ruta) : null;

        $errorLogOriginal = ini_get('error_log');
        $errorLogTemporal = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'error_log_test_'
            . bin2hex(random_bytes(4))
            . '.log';
        ini_set('error_log', $errorLogTemporal);

        try {
            [$status, $cuerpo, $json] = $this->manejar(
                new \RuntimeException('boom SQLSTATE 42S02 tabla usuarios')
            );

            $this->assertSame(500, $status);
            $this->assertFalse($json['success']);
            $this->assertSame('Error interno del servidor', $json['error']);

            $this->assertStringNotContainsString('boom', $cuerpo);
            $this->assertStringNotContainsStringIgnoringCase('sqlstate', $cuerpo);

            $this->assertFileExists($ruta);
            $contenido = (string) file_get_contents($ruta);
            $this->assertStringContainsString('RuntimeException', $contenido);
            $this->assertStringContainsString('boom SQLSTATE 42S02 tabla usuarios', $contenido);
            $this->assertStringContainsString('origen:', $contenido);
        } finally {
            if ($existia) {
                file_put_contents($ruta, $original);
            } elseif (is_file($ruta)) {
                @unlink($ruta);
            }

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
        $ruta = ErrorLog::ruta();
        $antes = is_file($ruta) ? (string) file_get_contents($ruta) : null;

        $this->manejar(new NotFoundException());

        $despues = is_file($ruta) ? (string) file_get_contents($ruta) : null;

        $this->assertSame($antes, $despues);
    }

    public function test_maneja_excepcion_controlada_con_codigo_fuera_de_rango_como_500(): void
    {
        [$status, $cuerpo, $json] = $this->manejar(new ExcepcionControladaConCodigoRaro());

        $this->assertSame(500, $status);
        $this->assertFalse($json['success']);
        $this->assertSame('mensaje raro', $json['error']);
    }
}
